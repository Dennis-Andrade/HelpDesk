<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

final class EntidadRepository extends BaseRepository
{
    private const TABLE = 'public.cooperativas';
    private const PK = 'id_cooperativa';
    private const VIEW_CARDS = 'public.v_cooperativas_cards';
    private const FUNC_CARDS = 'public.f_cooperativas_cards';
    private const TBL_SERVICIOS = 'public.cooperativa_servicio';
    private const COL_SERVICIO_COOP = 'id_cooperativa';
    private const COL_SERVICIO_ID = 'id_servicio';
    private const COL_SERVICIO_ACTIVO = 'activo';

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, perPage:int, page:int, limit:int, offset:int}
     */
    public function search(?string $q = null, int $limit = 20, int $offset = 0): array
    {
        $limit = $limit > 0 ? $limit : 20;
        $offset = $offset >= 0 ? $offset : 0;

        $term = $q !== null ? trim($q) : null;
        if ($term === '') {
            $term = null;
        }

        $sql = 'SELECT id, nombre, ruc, telefono, email, provincia, canton, segmento, servicios_text, activa, total
                FROM ' . self::FUNC_CARDS . '(:q, :limit, :offset)';

        $params = array(
            ':q'      => $term === null ? array(null, PDO::PARAM_NULL) : array($term, PDO::PARAM_STR),
            ':limit'  => array($limit, PDO::PARAM_INT),
            ':offset' => array($offset, PDO::PARAM_INT),
        );

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el listado de entidades.', 0, $e);
        }

        $total = 0;
        if (!empty($rows)) {
            $total = (int)($rows[0]['total'] ?? 0);
        }

        $page = $limit > 0 ? (int)floor($offset / $limit) + 1 : 1;

        return array(
            'items'   => $rows,
            'total'   => $total,
            'perPage' => $limit,
            'page'    => $page,
            'limit'   => $limit,
            'offset'  => $offset,
        );
    }

    public function findCardById(int $id): ?array
    {
        $sql = 'SELECT id, nombre, ruc, telefono, email, provincia, canton, segmento, servicios_text
                FROM ' . self::VIEW_CARDS . ' WHERE id = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener la entidad solicitada.', 0, $e);
        }

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = <<<'SQL'
SELECT
    c.id_cooperativa   AS id,
    c.nombre,
    c.ruc,
    c.telefono_fijo_1,
    c.telefono_movil,
    c.email,
    c.provincia_id,
    c.canton_id,
    c.tipo_entidad,
    c.id_segmento,
    c.notas,
    c.activa,
    c.servicio_activo,
    prov.nombre        AS provincia_nombre,
    cant.nombre        AS canton_nombre
FROM public.cooperativas c
LEFT JOIN public.provincias prov ON prov.id_provincia = c.provincia_id
LEFT JOIN public.cantones   cant ON cant.id_canton = c.canton_id
WHERE c.id_cooperativa = :id
LIMIT 1
SQL;

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener la entidad para edición.', 0, $e);
        }

        if ($row === null) {
            return null;
        }

        $row['servicios'] = $this->serviciosDeEntidad($id);
        $row['nit'] = $row['ruc'] ?? '';
        $row['telefono_fijo'] = $row['telefono_fijo_1'] ?? '';
        $row['segmento'] = isset($row['id_segmento']) && $row['id_segmento'] !== null ? (string)$row['id_segmento'] : '';

        return $row;
    }

    public function create(array $data): int
    {
        $payload = $this->mapEntidadParams($data);

        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    nombre,
                    ruc,
                    telefono_fijo_1,
                    telefono_movil,
                    email,
                    provincia_id,
                    canton_id,
                    tipo_entidad,
                    id_segmento,
                    notas,
                    activa,
                    servicio_activo
                ) VALUES (
                    :nombre,
                    :ruc,
                    :telefono_fijo,
                    :telefono_movil,
                    :email,
                    :provincia_id,
                    :canton_id,
                    :tipo_entidad,
                    :id_segmento,
                    :notas,
                    :activa,
                    :servicio_activo
                ) RETURNING id_cooperativa AS id';

        $params = array(
            ':nombre'         => array($payload['nombre'], PDO::PARAM_STR),
            ':ruc'            => $this->paramStringOrNull($payload['nit']),
            ':telefono_fijo'  => $this->paramStringOrNull($payload['telefono_fijo']),
            ':telefono_movil' => $this->paramStringOrNull($payload['telefono_movil']),
            ':email'          => $this->paramStringOrNull($payload['email']),
            ':provincia_id'   => $this->paramIntOrNull($payload['provincia_id']),
            ':canton_id'      => $this->paramIntOrNull($payload['canton_id']),
            ':tipo_entidad'   => array($payload['tipo_entidad'], PDO::PARAM_STR),
            ':id_segmento'    => $this->paramIntOrNull($payload['id_segmento']),
            ':notas'          => $this->paramStringOrNull($payload['notas']),
            ':activa'         => array($payload['activa'], PDO::PARAM_BOOL),
            ':servicio_activo'=> $this->paramBoolOrNull($payload['servicio_activo']),
        );

        $this->db->begin();
        try {
            $rows = $this->db->execute($sql, $params);
            if (!is_array($rows) || !isset($rows[0]['id'])) {
                throw new RuntimeException('INSERT cooperativas no devolvió id');
            }
            $id = (int)$rows[0]['id'];
            $this->replaceServiciosInternal($id, $payload['servicios']);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo registrar la entidad.', 0, $e);
        }

        return $id;
    }

    public function update(int $id, array $data): void
    {
        $payload = $this->mapEntidadParams($data);

        $sql = 'UPDATE ' . self::TABLE . ' SET
                    nombre = :nombre,
                    ruc = :ruc,
                    telefono_fijo_1 = :telefono_fijo,
                    telefono_movil = :telefono_movil,
                    email = :email,
                    provincia_id = :provincia_id,
                    canton_id = :canton_id,
                    tipo_entidad = :tipo_entidad,
                    id_segmento = :id_segmento,
                    notas = :notas,
                    activa = :activa,
                    servicio_activo = :servicio_activo
                WHERE ' . self::PK . ' = :id';

        $params = array(
            ':id'             => array($id, PDO::PARAM_INT),
            ':nombre'         => array($payload['nombre'], PDO::PARAM_STR),
            ':ruc'            => $this->paramStringOrNull($payload['nit']),
            ':telefono_fijo'  => $this->paramStringOrNull($payload['telefono_fijo']),
            ':telefono_movil' => $this->paramStringOrNull($payload['telefono_movil']),
            ':email'          => $this->paramStringOrNull($payload['email']),
            ':provincia_id'   => $this->paramIntOrNull($payload['provincia_id']),
            ':canton_id'      => $this->paramIntOrNull($payload['canton_id']),
            ':tipo_entidad'   => array($payload['tipo_entidad'], PDO::PARAM_STR),
            ':id_segmento'    => $this->paramIntOrNull($payload['id_segmento']),
            ':notas'          => $this->paramStringOrNull($payload['notas']),
            ':activa'         => array($payload['activa'], PDO::PARAM_BOOL),
            ':servicio_activo'=> $this->paramBoolOrNull($payload['servicio_activo']),
        );

        $this->db->begin();
        try {
            $this->db->execute($sql, $params);
            $this->replaceServiciosInternal($id, $payload['servicios']);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo actualizar la entidad.', 0, $e);
        }
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE ' . self::PK . ' = :id';

        $this->db->begin();
        try {
            $this->db->execute('DELETE FROM ' . self::TBL_SERVICIOS . ' WHERE ' . self::COL_SERVICIO_COOP . ' = :id', array(':id' => array($id, PDO::PARAM_INT)));
            $this->db->execute($sql, array(':id' => array($id, PDO::PARAM_INT)));
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo eliminar la entidad.', 0, $e);
        }
    }

    /**
     * @return array<int,int>
     */
    private function serviciosDeEntidad(int $id): array
    {
        $sql = 'SELECT ' . self::COL_SERVICIO_ID . ' FROM ' . self::TBL_SERVICIOS . ' WHERE ' . self::COL_SERVICIO_COOP . ' = :id';
        try {
            $rows = $this->db->fetchAll($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudieron obtener los servicios asignados.', 0, $e);
        }

        $ids = array();
        foreach ($rows as $row) {
            if (isset($row[self::COL_SERVICIO_ID])) {
                $ids[] = (int)$row[self::COL_SERVICIO_ID];
            } elseif (isset($row[0])) {
                $ids[] = (int)$row[0];
            }
        }

        return $ids;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function mapEntidadParams(array $data): array
    {
        $digits = static function ($value): string {
            return preg_replace('/\D+/', '', (string)$value);
        };

        $intOrNull = static function ($value): ?int {
            if ($value === null || $value === '') {
                return null;
            }
            if (is_int($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (int)$value;
            }
            return null;
        };

        $tipo = trim((string)($data['tipo_entidad'] ?? $data['tipo'] ?? ''));
        $permitidos = array('cooperativa', 'mutualista', 'sujeto_no_financiero', 'caja_ahorros', 'casa_valores');
        if ($tipo === '' || !in_array($tipo, $permitidos, true)) {
            $tipo = 'cooperativa';
        }

        $segmentoRaw = $data['id_segmento'] ?? $data['segmento'] ?? null;
        $segmentoId = $intOrNull($segmentoRaw);

        $telefonos = array(
            $digits($data['telefono_fijo'] ?? $data['telefono'] ?? $data['telefono_principal'] ?? ''),
            $digits($data['telefono_movil'] ?? $data['celular'] ?? ''),
        );

        $correo = trim((string)($data['email'] ?? ''));

        $servicios = $data['servicios'] ?? array();
        if (!is_array($servicios)) {
            $servicios = array();
        }
        $servicios = array_filter(array_map(static function ($value) {
            if (is_int($value)) {
                return $value > 0 ? $value : null;
            }
            if (is_numeric($value)) {
                $int = (int)$value;
                return $int > 0 ? $int : null;
            }
            return null;
        }, $servicios));
        $servicios = array_values(array_unique($servicios));

        return array(
            'nombre'          => trim((string)($data['nombre'] ?? '')),
            'nit'             => $digits($data['nit'] ?? $data['ruc'] ?? ''),
            'telefono_fijo'   => $telefonos[0] !== '' ? $telefonos[0] : null,
            'telefono_movil'  => $telefonos[1] !== '' ? $telefonos[1] : null,
            'email'           => $correo !== '' ? $correo : null,
            'provincia_id'    => $intOrNull($data['provincia_id'] ?? null),
            'canton_id'       => $intOrNull($data['canton_id'] ?? null),
            'tipo_entidad'    => $tipo,
            'id_segmento'     => $segmentoId,
            'notas'           => trim((string)($data['notas'] ?? '')),
            'servicios'       => $servicios,
            'activa'          => isset($data['activa']) ? (bool)$data['activa'] : true,
            'servicio_activo' => isset($data['servicio_activo']) ? (bool)$data['servicio_activo'] : null,
        );
    }

    private function replaceServiciosInternal(int $id, array $servicios): void
    {
        $ids = array();
        foreach ($servicios as $sid) {
            $sid = (int)$sid;
            if ($sid > 0 && !in_array($sid, $ids, true)) {
                $ids[] = $sid;
            }
        }

        if (in_array(1, $ids, true)) {
            $ids = array(1);
        }

        $this->db->execute('DELETE FROM ' . self::TBL_SERVICIOS . ' WHERE ' . self::COL_SERVICIO_COOP . ' = :id', array(
            ':id' => array($id, PDO::PARAM_INT),
        ));

        foreach ($ids as $sid) {
            $this->db->execute(
                'INSERT INTO ' . self::TBL_SERVICIOS . ' (' . self::COL_SERVICIO_COOP . ', ' . self::COL_SERVICIO_ID . ', ' . self::COL_SERVICIO_ACTIVO . ')
                 VALUES (:coop, :serv, true)',
                array(
                    ':coop' => array($id, PDO::PARAM_INT),
                    ':serv' => array($sid, PDO::PARAM_INT),
                )
            );
        }
    }

    private function paramStringOrNull(?string $value): array
    {
        if ($value === null || $value === '') {
            return array(null, PDO::PARAM_NULL);
        }

        return array($value, PDO::PARAM_STR);
    }

    private function paramIntOrNull(?int $value): array
    {
        if ($value === null) {
            return array(null, PDO::PARAM_NULL);
        }

        return array($value, PDO::PARAM_INT);
    }

    private function paramBoolOrNull(?bool $value): array
    {
        if ($value === null) {
            return array(null, PDO::PARAM_NULL);
        }

        return array($value, PDO::PARAM_BOOL);
    }
}
