<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
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
    private const COL_TELF          = 'telefono';
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
            '    COALESCE(c.servicio_activo, ' . "''" . ') AS servicio_activo,',
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
            "            SELECT NULLIF(TRIM(c." . self::COL_TELF . "), '') AS phone",
            "            UNION ALL",
            "            SELECT NULLIF(TRIM(c." . self::COL_TFIJ . "), '')",
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
        $sql = '
            SELECT
                ' . self::COL_ID . '    AS id_cooperativa,
                ' . self::COL_ID . '    AS id,
                ' . self::COL_ID . '    AS id_entidad,
                ' . self::COL_NOMBRE . '     AS nombre,
                ' . self::COL_RUC . '        AS nit,
                ' . self::COL_TFIJ . '      AS telefono_fijo_1,
                ' . self::COL_TMOV . '       AS telefono_movil,
                ' . self::COL_MAIL . '      AS email,
                ' . self::COL_PROV . '       AS provincia_id,
                ' . self::COL_CANTON . '     AS canton_id,
                ' . self::COL_TIPO . '       AS tipo_entidad,
                ' . self::COL_SEGMENTO . '   AS id_segmento,
                ' . self::COL_NOTAS . '      AS notas,
                ' . self::COL_ACTV . '     AS activa,
                CASE WHEN ' . self::COL_ACTV . ' THEN \'activo\' ELSE \'inactivo\' END AS estado
            FROM ' . self::T_COOP . '
            WHERE ' . self::COL_ID . ' = :id
            LIMIT 1
        ';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener la entidad.', 0, $e);
        }

        return $row ?: null;
    }

    public function findDetalles(int $id): ?array
    {
        $sql = '
        SELECT
            c.id_cooperativa                                      AS id_entidad,
            c.nombre,
            NULLIF(c.ruc, ' . "''" . ')                           AS ruc,
            NULLIF(c.telefono, ' . "''" . ')                       AS telefono,
            NULLIF(c.telefono_fijo_1, ' . "''" . ')                AS telefono_fijo_1,
            NULLIF(c.telefono_fijo_2, ' . "''" . ')                AS telefono_fijo_2,
            NULLIF(c.telefono_movil, ' . "''" . ')                 AS telefono_movil,
            NULLIF(c.email, ' . "''" . ')                          AS email,
            NULLIF(c.email2, ' . "''" . ')                         AS email2,
            NULLIF(c.email_raw, ' . "''" . ')                      AS email_raw,
            NULLIF(c.telefono_raw, ' . "''" . ')                    AS telefono_raw,
            NULLIF(c.telefono_fijo_1_raw, ' . "''" . ')            AS telefono_fijo_1_raw,
            NULLIF(c.telefono_fijo_2_raw, ' . "''" . ')            AS telefono_fijo_2_raw,
            NULLIF(c.telefono_movil_raw, ' . "''" . ')             AS telefono_movil_raw,
            c.provincia_id,
            c.canton_id,
            COALESCE(NULLIF(c.provincia, ' . "''" . '), prov.nombre) AS provincia,
            COALESCE(NULLIF(c.canton, ' . "''" . '), can.nombre)     AS canton,
            c.tipo_entidad,
            c.id_segmento,
            seg.nombre_segmento                                   AS segmento_nombre,
            COALESCE(c.servicio_activo, ' . "''" . ')             AS servicio_activo,
            c.notas
        FROM public.cooperativas c
        LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
        LEFT JOIN public.provincia prov ON prov.id = c.provincia_id
        LEFT JOIN public.canton    can  ON can.id = c.canton_id
        WHERE c.id_cooperativa = :id
        LIMIT 1
        ';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
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
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener los servicios activos.', 0, $e);
        }
    }

    /** Crear y devolver el id nuevo */
    public function create(array $d): int
    {
        $sql = '
            INSERT INTO ' . self::T_COOP . '
                (
                    ' . self::COL_NOMBRE . ',
                    ' . self::COL_RUC . ',
                    ' . self::COL_TFIJ . ',
                    ' . self::COL_TMOV . ',
                    ' . self::COL_MAIL . ',
                    ' . self::COL_PROV . ',
                    ' . self::COL_CANTON . ',
                    ' . self::COL_TIPO . ',
                    ' . self::COL_SEGMENTO . ',
                    ' . self::COL_NOTAS . ',
                    ' . self::COL_ACTV . '
                )
            VALUES
                (
                    :nombre,
                    :ruc,
                    :tfijo,
                    :tmov,
                    :email,
                    :prov,
                    :canton,
                    :tipo,
                    :segmento,
                    :notas,
                    :activa
                )
            RETURNING ' . self::COL_ID . ' AS id
        ';

        $params = $this->buildEntidadParams($d);

        try {
            $rows = $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al crear la entidad.', 0, $e);
        }

        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : null;
        if (!$row || !isset($row['id'])) {
            throw new RuntimeException('INSERT cooperativas no devolvió id');
        }

        return (int)$row['id'];
    }

    /** Actualizar */
    public function update(int $id, array $d): void
    {
        $sql = '
            UPDATE ' . self::T_COOP . ' SET
                ' . self::COL_NOMBRE . '   = :nombre,
                ' . self::COL_RUC . '      = :ruc,
                ' . self::COL_TFIJ . '    = :tfijo,
                ' . self::COL_TMOV . '     = :tmov,
                ' . self::COL_MAIL . '    = :email,
                ' . self::COL_PROV . '     = :prov,
                ' . self::COL_CANTON . '   = :canton,
                ' . self::COL_TIPO . '     = :tipo,
                ' . self::COL_SEGMENTO . ' = :segmento,
                ' . self::COL_NOTAS . '    = :notas,
                ' . self::COL_ACTV . '   = :activa
            WHERE ' . self::COL_ID . ' = :id
        ';

        $params = $this->buildEntidadParams($d);
        $params[':id'] = array($id, PDO::PARAM_INT);

        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al actualizar la entidad.', 0, $e);
        }
    }

    /** Eliminar */
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::T_COOP . ' WHERE ' . self::COL_ID . ' = :id';
        try {
            $this->db->execute($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al eliminar la entidad.', 0, $e);
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
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener los servicios de la entidad.', 0, $e);
        }

        $ids = array();
        foreach ($rows as $row) {
            if (isset($row['id_servicio'])) {
                $ids[] = (int)$row['id_servicio'];
            }
        }
        return $ids;
    }

    /** Reemplazar relación servicios (Matrix=1 exclusivo) */
    public function replaceServicios(int $id, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if (in_array(1, $ids, true)) {
            $ids = array(1);
        }

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

            $this->db->commit();
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
                'telefono'         => isset($telefonos[0]) ? $telefonos[0] : null,
                'emails'           => $emails,
                'email'            => isset($emails[0]) ? $emails[0] : null,
                'servicios'        => $servicios,
                'servicios_count'  => isset($row['servicios_count']) ? (int)$row['servicios_count'] : 0,
                'tipo_entidad'     => isset($row['tipo_entidad']) ? (string)$row['tipo_entidad'] : null,
                'id_segmento'      => isset($row['id_segmento']) ? (int)$row['id_segmento'] : null,
                'provincia_id'     => isset($row['provincia_id']) && $row['provincia_id'] !== null ? (int)$row['provincia_id'] : null,
                'canton_id'        => isset($row['canton_id']) && $row['canton_id'] !== null ? (int)$row['canton_id'] : null,
                'servicio_activo'  => isset($row['servicio_activo']) ? (string)$row['servicio_activo'] : null,
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
     * @param array<string,mixed> $d
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildEntidadParams(array $d): array
    {
        $params = array(
            ':nombre'  => array($d['nombre'], PDO::PARAM_STR),
            ':ruc'     => $this->nullableStringParam($d['nit'] ?? ''),
            ':tfijo'   => $this->nullableStringParam($d['telefono_fijo'] ?? ''),
            ':tmov'    => $this->nullableStringParam($d['telefono_movil'] ?? ''),
            ':email'   => $this->nullableStringParam($d['email'] ?? ''),
            ':prov'    => $this->nullableIntParam($d['provincia_id'] ?? null),
            ':canton'  => $this->nullableIntParam($d['canton_id'] ?? null),
            ':tipo'    => array($d['tipo_entidad'], PDO::PARAM_STR),
            ':segmento'=> $this->nullableIntParam($d['id_segmento'] ?? null),
            ':notas'   => $this->nullableStringParam($d['notas'] ?? ''),
            ':activa'  => array($this->resolveActiva($d), PDO::PARAM_BOOL),
        );

        return $params;
    }

    private function resolveActiva(array $d): bool
    {
        if (array_key_exists('activa', $d)) {
            return (bool)$d['activa'];
        }
        $estado = isset($d['estado']) ? (string)$d['estado'] : 'activo';
        return $estado === 'activo';
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
        $value = (string)$value;
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
