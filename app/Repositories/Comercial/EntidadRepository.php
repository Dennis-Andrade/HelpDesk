<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use App\Support\Logger;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Repositorio de Cooperativas (Comercial).
 * - Bindea tipos correctamente (STR/NULL, INT/NULL, BOOL)
 * - Provee helpers de segmentos, servicios y pivot de relación
 * - SELECT con alias que el formulario espera (ruc AS nit, estado)
 */
final class EntidadRepository extends BaseRepository
{
    private const T_COOP            = 'public.cooperativas';
    private const COL_ID            = 'id_cooperativa';
    private const COL_NOMBRE        = 'nombre';
    private const COL_RUC           = 'ruc';
    private const COL_TFIJ          = 'telefono_fijo_1';
    private const COL_TMOV          = 'telefono_movil';
    private const COL_MAIL          = 'email';
    private const COL_ACTV          = 'activa';
    private const COL_PROV          = 'provincia_id';
    private const COL_CANTON        = 'canton_id';
    private const COL_TIPO          = 'tipo_entidad';
    private const COL_SEGMENTO      = 'id_segmento';
    private const COL_NOTAS         = 'notas';

    private const T_SERV            = 'public.servicios';
    private const COL_ID_SERV       = 'id_servicio';
    private const COL_NOM_SERV      = 'nombre_servicio';
    private const COL_SERV_ACTIVO   = 'activo';

    private const T_SEG             = 'public.segmentos';
    private const COL_ID_SEG        = 'id_segmento';
    private const COL_NOM_SEG       = 'nombre_segmento';

    private const T_PIVOT           = 'public.cooperativa_servicio';
    private const PIV_COOP          = 'id_cooperativa';
    private const PIV_SERV          = 'id_servicio';
    private const PIV_ACTIVO        = 'activo';

    /**
     * Búsqueda paginada.
     *
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function search(string $q, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $q     = trim($q);
        $hasQ  = $q !== '' ? 1 : 0;
        $qLike = '%' . $q . '%';

        $countSql = '
            SELECT COUNT(*) AS total
            FROM ' . self::T_COOP . ' c
            WHERE (
                :has_q = 0
                OR (
                    unaccent(lower(c.' . self::COL_NOMBRE . ')) LIKE unaccent(lower(:q_like))
                    OR c.' . self::COL_RUC . ' LIKE :q_like
                )
            )
        ';

        $bindings = array(
            ':has_q'  => array($hasQ, PDO::PARAM_INT),
            ':q_like' => array($qLike, PDO::PARAM_STR),
        );

        try {
            $countRow = $this->db->fetch($countSql, $bindings);
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al contar entidades.', __METHOD__ . '::count');
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar entidades.', 0, $e);
        }

        $total = $countRow ? (int)$countRow['total'] : 0;

        if ($total === 0) {
            return array(
                'items'   => array(),
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            );
        }

        $sqlLines = array(
            'SELECT',
            '    c.' . self::COL_ID . ' AS id,',
            '    c.' . self::COL_NOMBRE . ' AS nombre,',
            '    c.' . self::COL_RUC . ' AS ruc,',
            '    c.' . self::COL_TIPO . ' AS tipo_entidad,',
            '    c.' . self::COL_SEGMENTO . ' AS id_segmento,',
            '    seg.' . self::COL_NOM_SEG . ' AS segmento_nombre,',
            '    COALESCE(c.' . self::COL_PROV . ', df.provincia_id) AS provincia_id,',
            '    prov.nombre AS provincia_nombre,',
            '    COALESCE(c.' . self::COL_CANTON . ', df.canton_id) AS canton_id,',
            '    can.nombre AS canton_nombre,',
            '    phone_data.telefonos_json,',
            '    email_data.emails_json,',
            '    svc.servicios_json,',
            '    COALESCE(svc.servicios_count, 0) AS servicios_count',
            'FROM ' . self::T_COOP . ' c',
            'LEFT JOIN ' . self::T_SEG . ' seg',
            '  ON seg.' . self::COL_ID_SEG . ' = c.' . self::COL_SEGMENTO,
            'LEFT JOIN public.datos_facturacion df',
            '  ON df.id_cooperativa = c.' . self::COL_ID,
            'LEFT JOIN public.provincia prov',
            '  ON prov.id = COALESCE(c.' . self::COL_PROV . ', df.provincia_id)',
            'LEFT JOIN public.canton can',
            '  ON can.id = COALESCE(c.' . self::COL_CANTON . ', df.canton_id)',
            'LEFT JOIN LATERAL (',
            '    SELECT json_agg(phone ORDER BY phone) AS telefonos_json',
            '    FROM (',
            '        SELECT DISTINCT phone',
            '        FROM (',
            "            SELECT NULLIF(TRIM(c." . self::COL_TFIJ . "), '') AS phone",
            "            UNION ALL",
            "            SELECT NULLIF(TRIM(c." . self::COL_TMOV . "), '')",
            '        ) AS raw',
            '        WHERE phone IS NOT NULL',
            '    ) AS phones',
            ') AS phone_data ON TRUE',
            'LEFT JOIN LATERAL (',
            '    SELECT json_agg(email ORDER BY email) AS emails_json',
            '    FROM (',
            '        SELECT DISTINCT email',
            '        FROM (',
            "            SELECT NULLIF(TRIM(c." . self::COL_MAIL . "), '') AS email",
            '        ) AS raw',
            '        WHERE email IS NOT NULL',
            '    ) AS emails',
            ') AS email_data ON TRUE',
            'LEFT JOIN LATERAL (',
            '    SELECT',
            '        json_agg(nombre_servicio ORDER BY nombre_servicio) AS servicios_json,',
            '        COUNT(*) AS servicios_count',
            '    FROM (',
            '        SELECT DISTINCT s.' . self::COL_NOM_SERV . ' AS nombre_servicio',
            '        FROM ' . self::T_PIVOT . ' cs',
            '        JOIN ' . self::T_SERV . ' s ON s.' . self::COL_ID_SERV . ' = cs.' . self::PIV_SERV,
            '        WHERE cs.' . self::PIV_COOP . ' = c.' . self::COL_ID,
            '          AND cs.' . self::PIV_ACTIVO . ' = true',
            '    ) AS svc_names',
            ') AS svc ON TRUE',
            'WHERE (',
            '    :has_q = 0',
            '    OR (',
            '        unaccent(lower(c.' . self::COL_NOMBRE . ')) LIKE unaccent(lower(:q_like))',
            '        OR c.' . self::COL_RUC . ' LIKE :q_like',
            '    )',
            ')',
            'ORDER BY c.' . self::COL_NOMBRE,
            'LIMIT :limit OFFSET :offset',
        );

        $sql = implode("\n", $sqlLines);

        $queryParams = $bindings;
        $queryParams[':limit']  = array($perPage, PDO::PARAM_INT);
        $queryParams[':offset'] = array($offset, PDO::PARAM_INT);

        try {
            $items = $this->db->fetchAll($sql, $queryParams);
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al buscar entidades.', __METHOD__);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al buscar entidades.', 0, $e);
        }

        $items = $this->hydrateListado($items);

        return array(
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        );
    }

    public function findById(int $id): ?array
    {
        $sqlLines = array(
            'SELECT',
            '    c.' . self::COL_ID . '           AS id,',
            '    c.' . self::COL_NOMBRE . '       AS nombre,',
            '    c.' . self::COL_RUC . '          AS ruc,',
            '    c.' . self::COL_TFIJ . '         AS telefono_fijo_1,',
            '    c.' . self::COL_TMOV . '         AS telefono_movil,',
            '    c.' . self::COL_MAIL . '         AS email,',
            '    c.' . self::COL_PROV . '         AS provincia_id,',
            '    c.' . self::COL_CANTON . '       AS canton_id,',
            '    c.' . self::COL_TIPO . '         AS tipo_entidad,',
            '    c.' . self::COL_SEGMENTO . '     AS id_segmento,',
            '    c.' . self::COL_NOTAS . '        AS notas,',
            '    c.' . self::COL_ACTV . '         AS activa,',
            '    c.' . self::COL_SERV_ACTIVO . '  AS servicio_activo',
            'FROM ' . self::T_COOP . ' c',
            'WHERE c.' . self::COL_ID . ' = :id',
            'LIMIT 1',
        );

        $sql = implode("\n", $sqlLines);

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al obtener la entidad.', __METHOD__);
        }

        if (!$row) {
            return null;
        }

        $dto = $this->mapRowToDto($row);
        $dto['servicios'] = $this->serviciosDeEntidad($id);
        $dto['nit'] = $dto['ruc'];

        return $dto;
    }

    public function findDetalles(int $id): ?array
    {
        $sql = '
        SELECT
            c.id_cooperativa                                        AS id_entidad,
            c.nombre,
            NULLIF(c.ruc, ' . "''" . ')                             AS ruc,
            NULLIF(c.telefono_fijo_1, ' . "''" . ')                  AS telefono_fijo_1,
            NULLIF(c.telefono_movil, ' . "''" . ')                   AS telefono_movil,
            NULLIF(c.email, ' . "''" . ')                            AS email,
            COALESCE(c.provincia_id, df.provincia_id)               AS provincia_id,
            COALESCE(c.canton_id, df.canton_id)                     AS canton_id,
            prov.nombre                                             AS provincia,
            can.nombre                                              AS canton,
            c.tipo_entidad,
            c.id_segmento,
            seg.nombre_segmento                                     AS segmento_nombre,
            c.notas
        FROM public.cooperativas c
        LEFT JOIN public.datos_facturacion df ON df.id_cooperativa = c.id_cooperativa
        LEFT JOIN public.provincia prov ON prov.id = COALESCE(c.provincia_id, df.provincia_id)
        LEFT JOIN public.canton    can  ON can.id = COALESCE(c.canton_id, df.canton_id)
        LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
        WHERE c.id_cooperativa = :id
        LIMIT 1
        ';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al obtener el detalle de la entidad.', __METHOD__);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el detalle de la entidad.', 0, $e);
        }

        return $row ?: null;
    }

    public function serviciosActivos(int $id): array
    {
        $sql = '
        SELECT s.id_servicio, s.nombre_servicio
        FROM public.cooperativa_servicio cs
        JOIN public.servicios s ON s.id_servicio = cs.id_servicio
        WHERE cs.id_cooperativa = :id AND cs.activo = true
        ORDER BY s.nombre_servicio
        ';

        try {
            return $this->db->fetchAll($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al obtener los servicios activos.', __METHOD__);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener los servicios activos.', 0, $e);
        }
    }

    /** Crear y devolver el id nuevo */
    public function create(array $dto): int
    {
        $data = $this->normalizeDto($dto);

        $sqlLines = array(
            'INSERT INTO ' . self::T_COOP,
            '    (',
            '        ' . self::COL_NOMBRE . ',',
            '        ' . self::COL_RUC . ',',
            '        ' . self::COL_TFIJ . ',',
            '        ' . self::COL_TMOV . ',',
            '        ' . self::COL_MAIL . ',',
            '        ' . self::COL_PROV . ',',
            '        ' . self::COL_CANTON . ',',
            '        ' . self::COL_TIPO . ',',
            '        ' . self::COL_SEGMENTO . ',',
            '        ' . self::COL_NOTAS . ',',
            '        ' . self::COL_ACTV . ',',
            '        ' . self::COL_SERV_ACTIVO,
            '    )',
            'VALUES',
            '    (',
            '        :nombre,',
            '        :ruc,',
            '        :telefono_fijo_1,',
            '        :telefono_movil,',
            '        :email,',
            '        :provincia_id,',
            '        :canton_id,',
            '        :tipo_entidad,',
            '        :id_segmento,',
            '        :notas,',
            '        :activa,',
            '        :servicio_activo',
            '    )',
            'RETURNING ' . self::COL_ID . ' AS id',
        );

        $sql = implode("\n", $sqlLines);

        $params = array(
            ':nombre'          => array($data['nombre'], PDO::PARAM_STR),
            ':ruc'             => $this->nullableStringParam($data['ruc']),
            ':telefono_fijo_1' => $this->nullableStringParam($data['telefono_fijo_1']),
            ':telefono_movil'  => $this->nullableStringParam($data['telefono_movil']),
            ':email'           => $this->nullableStringParam($data['email']),
            ':provincia_id'    => $this->nullableIntParam($data['provincia_id']),
            ':canton_id'       => $this->nullableIntParam($data['canton_id']),
            ':tipo_entidad'    => array($data['tipo_entidad'], PDO::PARAM_STR),
            ':id_segmento'     => $this->nullableIntParam($data['id_segmento']),
            ':notas'           => $this->nullableStringParam($data['notas']),
            ':activa'          => array($data['activa'], PDO::PARAM_BOOL),
            ':servicio_activo' => array($data['servicio_activo'], PDO::PARAM_BOOL),
        );

        try {
            $rows = $this->db->execute($sql, $params);
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al crear la entidad.', __METHOD__);
        }

        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : null;
        if (!$row || !isset($row['id'])) {
            throw new RuntimeException('INSERT cooperativas no devolvió id');
        }

        return (int)$row['id'];
    }

    /** Actualizar */
    public function update(int $id, array $dto): bool
    {
        $data = $this->normalizeDto($dto);

        $sqlLines = array(
            'UPDATE ' . self::T_COOP,
            'SET',
            '    ' . self::COL_NOMBRE . '       = :nombre,',
            '    ' . self::COL_RUC . '          = :ruc,',
            '    ' . self::COL_TFIJ . '         = :telefono_fijo_1,',
            '    ' . self::COL_TMOV . '         = :telefono_movil,',
            '    ' . self::COL_MAIL . '         = :email,',
            '    ' . self::COL_PROV . '         = :provincia_id,',
            '    ' . self::COL_CANTON . '       = :canton_id,',
            '    ' . self::COL_TIPO . '         = :tipo_entidad,',
            '    ' . self::COL_SEGMENTO . '     = :id_segmento,',
            '    ' . self::COL_NOTAS . '        = :notas,',
            '    ' . self::COL_ACTV . '         = :activa,',
            '    ' . self::COL_SERV_ACTIVO . '  = :servicio_activo',
            'WHERE ' . self::COL_ID . ' = :id',
        );

        $sql = implode("\n", $sqlLines);

        $params = array(
            ':nombre'          => array($data['nombre'], PDO::PARAM_STR),
            ':ruc'             => $this->nullableStringParam($data['ruc']),
            ':telefono_fijo_1' => $this->nullableStringParam($data['telefono_fijo_1']),
            ':telefono_movil'  => $this->nullableStringParam($data['telefono_movil']),
            ':email'           => $this->nullableStringParam($data['email']),
            ':provincia_id'    => $this->nullableIntParam($data['provincia_id']),
            ':canton_id'       => $this->nullableIntParam($data['canton_id']),
            ':tipo_entidad'    => array($data['tipo_entidad'], PDO::PARAM_STR),
            ':id_segmento'     => $this->nullableIntParam($data['id_segmento']),
            ':notas'           => $this->nullableStringParam($data['notas']),
            ':activa'          => array($data['activa'], PDO::PARAM_BOOL),
            ':servicio_activo' => array($data['servicio_activo'], PDO::PARAM_BOOL),
            ':id'              => array($id, PDO::PARAM_INT),
        );

        try {
            $this->db->execute($sql, $params);
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al actualizar la entidad.', __METHOD__);
        }

        return true;
    }

    /** Eliminar */
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::T_COOP . ' WHERE ' . self::COL_ID . ' = :id';
        try {
            $this->db->execute($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al eliminar la entidad.', __METHOD__);
        }
    }

    /** Catálogo de servicios activos */
    public function servicios(): array
    {
        $sql = 'SELECT ' . self::COL_ID_SERV . ' AS id_servicio, ' . self::COL_NOM_SERV . ' AS nombre_servicio'
                . ' FROM ' . self::T_SERV
                . ' WHERE ' . self::COL_SERV_ACTIVO . ' = true'
                . ' ORDER BY ' . self::COL_ID_SERV;
        try {
            return $this->db->fetchAll($sql);
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al obtener los servicios.', __METHOD__);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener los servicios.', 0, $e);
        }
    }

    /** Catálogo de segmentos (1..5) */
    public function segmentos(): array
    {
        $sql = 'SELECT ' . self::COL_ID_SEG . ' AS id_segmento, ' . self::COL_NOM_SEG . ' AS nombre_segmento'
                . ' FROM ' . self::T_SEG
                . ' ORDER BY ' . self::COL_ID_SEG;
        try {
            return $this->db->fetchAll($sql);
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al obtener los segmentos.', __METHOD__);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener los segmentos.', 0, $e);
        }
    }

    /** IDs de servicios asignados a una entidad */
    public function serviciosDeEntidad(int $id): array
    {
        $sql = 'SELECT ' . self::PIV_SERV . ' AS id_servicio FROM ' . self::T_PIVOT . ' WHERE ' . self::PIV_COOP . ' = :id';
        try {
            $rows = $this->db->fetchAll($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (PDOException $e) {
            throw $this->wrapPdoException($e, 'Error al obtener los servicios de la entidad.', __METHOD__);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener los servicios de la entidad.', 0, $e);
        }

        $ids = array();
        foreach ($rows as $row) {
            if (isset($row['id_servicio'])) {
                $ids[] = (int)$row['id_servicio'];
            }
        }

        return $this->normalizeServicios($ids, false);
    }

    /** Reemplazar relación servicios (Matrix=1 exclusivo) */
    public function replaceServicios(int $id, array $ids): void
    {
        $ids = $this->normalizeServicios($ids, true);

        $this->db->begin();
        try {
            $this->db->execute('DELETE FROM ' . self::T_PIVOT . ' WHERE ' . self::PIV_COOP . ' = :id', array(
                ':id' => array($id, PDO::PARAM_INT),
            ));

            if (!empty($ids)) {
                $sql = '
                    INSERT INTO ' . self::T_PIVOT . ' (' . self::PIV_COOP . ', ' . self::PIV_SERV . ', ' . self::PIV_ACTIVO . ')
                    VALUES (:c, :s, true)
                ';
                foreach ($ids as $sid) {
                    $this->db->execute($sql, array(
                        ':c' => array($id, PDO::PARAM_INT),
                        ':s' => array($sid, PDO::PARAM_INT),
                    ));
                }
            }

            $this->db->execute(
                'UPDATE ' . self::T_COOP . ' SET ' . self::COL_SERV_ACTIVO . ' = :activo WHERE ' . self::COL_ID . ' = :id',
                array(
                    ':activo' => array(!empty($ids), PDO::PARAM_BOOL),
                    ':id'     => array($id, PDO::PARAM_INT),
                )
            );

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $this->wrapPdoException($e, 'Error al actualizar los servicios de la entidad.', __METHOD__);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('Error al actualizar los servicios de la entidad.', 0, $e);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function hydrateListado(array $rows): array
    {
        $hydrated = array();

        foreach ($rows as $row) {
            $id = isset($row['id']) ? (int)$row['id'] : 0;

            $telefonos = $this->decodeJsonList($row['telefonos_json'] ?? null);
            $emails    = $this->decodeJsonList($row['emails_json'] ?? null);
            $servicios = $this->decodeJsonList($row['servicios_json'] ?? null);

            $hydrated[] = array(
                'id'               => $id,
                'nombre'           => isset($row['nombre']) ? (string)$row['nombre'] : '',
                'ruc'              => isset($row['ruc']) ? (string)$row['ruc'] : null,
                'segmento_nombre'  => isset($row['segmento_nombre']) ? (string)$row['segmento_nombre'] : null,
                'provincia_nombre' => isset($row['provincia_nombre']) ? (string)$row['provincia_nombre'] : null,
                'canton_nombre'    => isset($row['canton_nombre']) ? (string)$row['canton_nombre'] : null,
                'telefonos'        => $telefonos,
                'emails'           => $emails,
                'servicios'        => $servicios,
                'servicios_count'  => isset($row['servicios_count']) ? (int)$row['servicios_count'] : 0,
                'tipo_entidad'     => isset($row['tipo_entidad']) ? (string)$row['tipo_entidad'] : null,
                'id_segmento'      => isset($row['id_segmento']) ? (int)$row['id_segmento'] : null,
                'provincia_id'     => isset($row['provincia_id']) && $row['provincia_id'] !== null ? (int)$row['provincia_id'] : null,
                'canton_id'        => isset($row['canton_id']) && $row['canton_id'] !== null ? (int)$row['canton_id'] : null,
            );
        }

        return $hydrated;
    }

    /**
     * @param mixed $json
     * @return array<int,string>
     */
    private function decodeJsonList($json): array
    {
        if ($json === null || $json === '') {
            return array();
        }

        if (is_array($json)) {
            $list = $json;
        } else {
            $decoded = json_decode((string)$json, true);
            $list    = is_array($decoded) ? $decoded : array();
        }

        $clean = array();
        foreach ($list as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $trimmed = trim((string)$value);
            if ($trimmed === '') {
                continue;
            }
            if (!in_array($trimmed, $clean, true)) {
                $clean[] = $trimmed;
            }
        }

        return $clean;
    }
    /**
     * @param array<string,mixed> $dto
     * @return array<string,mixed>
     */
    private function normalizeDto(array $dto): array
    {
        $nombre = trim((string)($dto['nombre'] ?? ''));
        if ($nombre === '') {
            throw new RuntimeException('El nombre es obligatorio');
        }

        $tipoEntidad = isset($dto['tipo_entidad']) ? (string)$dto['tipo_entidad'] : 'cooperativa';
        $tipoEntidad = $this->sanitizeTipoEntidad($tipoEntidad);

        $segmento = $this->intOrNull($dto['id_segmento'] ?? null);
        if ($tipoEntidad !== 'cooperativa') {
            $segmento = null;
        }

        $servicios = $this->normalizeServicios($dto['servicios'] ?? array(), false);

        $email = $this->stringOrNull($dto['email'] ?? null);
        if ($email !== null) {
            $email = strtolower($email);
        }

        $activa = array_key_exists('activa', $dto) ? (bool)$dto['activa'] : true;
        $servicioActivo = array_key_exists('servicio_activo', $dto)
            ? (bool)$dto['servicio_activo']
            : !empty($servicios);

        return array(
            'nombre'          => $nombre,
            'ruc'             => $this->digitsOrNull($dto['ruc'] ?? null),
            'telefono'        => $this->digitsOrNull($dto['telefono'] ?? null),
            'telefono_fijo_1' => $this->digitsOrNull($dto['telefono_fijo_1'] ?? null),
            'telefono_movil'  => $this->digitsOrNull($dto['telefono_movil'] ?? null),
            'email'           => $email,
            'provincia_id'    => $this->intOrNull($dto['provincia_id'] ?? null),
            'canton_id'       => $this->intOrNull($dto['canton_id'] ?? null),
            'tipo_entidad'    => $tipoEntidad,
            'id_segmento'     => $segmento,
            'notas'           => $this->stringOrNull($dto['notas'] ?? null),
            'servicios'       => $servicios,
            'activa'          => $activa,
            'servicio_activo' => $servicioActivo,
        );
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapRowToDto(array $row): array
    {
        $idValue = $row['id'] ?? $row[self::COL_ID] ?? null;

        return array(
            'id'              => $idValue !== null ? (int)$idValue : null,
            'nombre'          => isset($row['nombre']) ? (string)$row['nombre'] : '',
            'ruc'             => $this->stringOrNull($row['ruc'] ?? null),
            'telefono'        => $this->stringOrNull($row['telefono'] ?? null),
            'telefono_fijo_1' => $this->stringOrNull($row['telefono_fijo_1'] ?? null),
            'telefono_movil'  => $this->stringOrNull($row['telefono_movil'] ?? null),
            'email'           => $this->stringOrNull($row['email'] ?? null),
            'provincia_id'    => $this->intOrNull($row['provincia_id'] ?? null),
            'canton_id'       => $this->intOrNull($row['canton_id'] ?? null),
            'tipo_entidad'    => isset($row['tipo_entidad']) ? (string)$row['tipo_entidad'] : null,
            'id_segmento'     => $this->intOrNull($row['id_segmento'] ?? null),
            'notas'           => $this->stringOrNull($row['notas'] ?? null),
            'activa'          => isset($row['activa']) ? (bool)$row['activa'] : true,
            'servicio_activo' => isset($row['servicio_activo']) ? (bool)$row['servicio_activo'] : false,
        );
    }

    /**
     * @param array<mixed> $ids
     * @return array<int,int>
     */
    private function normalizeServicios($ids, bool $validateCatalog): array
    {
        if (!is_array($ids)) {
            $ids = array();
        }

        $clean = array_values(array_unique(array_map(static function ($value): int {
            return (int)$value;
        }, array_filter($ids, static function ($value): bool {
            return is_numeric($value) || is_int($value);
        }))));

        $clean = array_values(array_filter($clean, static function (int $value): bool {
            return $value > 0;
        }));

        if (in_array(1, $clean, true)) {
            $clean = array(1);
        }

        if ($validateCatalog && !empty($clean)) {
            $catalog = $this->servicios();
            $valid = array();
            foreach ($catalog as $servicio) {
                $sid = null;
                if (isset($servicio['id_servicio'])) {
                    $sid = (int)$servicio['id_servicio'];
                } elseif (isset($servicio['id'])) {
                    $sid = (int)$servicio['id'];
                }
                if ($sid !== null && $sid > 0) {
                    $valid[$sid] = true;
                }
            }

            $clean = array_values(array_filter($clean, static function (int $value) use ($valid): bool {
                return isset($valid[$value]);
            }));
        }

        return $clean;
    }

    private function sanitizeTipoEntidad(string $tipo): string
    {
        $tipo = trim($tipo);
        $permitidos = array('cooperativa','mutualista','sujeto_no_financiero','caja_ahorros','casa_valores');
        if ($tipo === '' || !in_array($tipo, $permitidos, true)) {
            return 'cooperativa';
        }
        return $tipo;
    }

    private function wrapPdoException(PDOException $e, string $message, string $context): RuntimeException
    {
        Logger::error($e, $context);
        return new RuntimeException($message, 0, $e);
    }

    /**
     * @param mixed $value
     */
    private function digitsOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string)$value);
        return $digits === '' ? null : $digits;
    }

    /**
     * @param mixed $value
     */
    private function stringOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param mixed $value
     */
    private function intOrNull($value): ?int
    {
        if ($value === null) {
            return null;
        }
        if ($value === '' || $value === false) {
            return null;
        }
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_numeric($value)) {
            $int = (int)$value;
            return $int > 0 ? $int : null;
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return array{0:mixed,1:int}
     */
    private function nullableStringParam($value): array
    {
        if ($value === null) {
            return array(null, PDO::PARAM_NULL);
        }
        $value = trim((string)$value);
        if ($value === '') {
            return array(null, PDO::PARAM_NULL);
        }
        return array($value, PDO::PARAM_STR);
    }

    /**
     * @param mixed $value
     * @return array{0:mixed,1:int}
     */
    private function nullableIntParam($value): array
    {
        if ($value === null || $value === '') {
            return array(null, PDO::PARAM_NULL);
        }
        return array((int)$value, PDO::PARAM_INT);
    }
}
