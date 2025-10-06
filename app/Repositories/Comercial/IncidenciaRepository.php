<?php
namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

/**
 * Repositorio para gestionar las incidencias del módulo comercial.
 */
final class IncidenciaRepository extends BaseRepository
{
    private const T_INCIDENCIA = 'public.incidencias_comercial';
    private const COL_ID       = 'id_incidencia';
    private const COL_COOP     = 'id_cooperativa';
    private const COL_ASUNTO   = 'asunto';
    private const COL_DESCRIP  = 'descripcion';
    private const COL_PRIOR    = 'prioridad';
    private const COL_ESTADO   = 'estado';
    private const COL_TIPO     = 'tipo_incidencia';
    private const COL_TICKET   = 'id_ticket';
    private const COL_CREATED  = 'created_at';
    private const COL_UPDATED  = 'updated_at';
    private const COL_USER     = 'creado_por';

    private const T_COOP       = 'public.cooperativas';
    private const COOP_ID      = 'id_cooperativa';
    private const COOP_NOMBRE  = 'nombre';

    private const T_CONTACTOS  = 'public.agenda_contactos';
    private const CONTACT_COOP = 'id_cooperativa';

    /**
     * Obtiene un listado paginado de incidencias según filtros.
     *
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $estado   = isset($filters['estado']) ? trim((string)$filters['estado']) : '';
        $coopId   = isset($filters['coop']) ? (int)$filters['coop'] : 0;
        $ticket   = isset($filters['ticket']) ? trim((string)$filters['ticket']) : '';
        $ticketId = (int)preg_replace('/\D+/', '', $ticket);

        $ticketExpr = "CONCAT('INC-', TO_CHAR(COALESCE(i." . self::COL_CREATED . ", CURRENT_DATE), 'YYYY'), '-', LPAD(i." . self::COL_ID . "::text, 5, '0'))";

        $where   = [];
        $bindings = [];

        if ($estado !== '') {
            $where[] = 'i.' . self::COL_ESTADO . ' = :estado';
            $bindings[':estado'] = [$estado, PDO::PARAM_STR];
        }

        if ($coopId > 0) {
            $where[] = 'i.' . self::COL_COOP . ' = :coop_id';
            $bindings[':coop_id'] = [$coopId, PDO::PARAM_INT];
        }

        if ($ticket !== '') {
            $where[] = '(' . $ticketExpr . ' ILIKE :ticket_like' . ($ticketId > 0 ? ' OR COALESCE(i.' . self::COL_TICKET . ', i.' . self::COL_ID . ') = :ticket_id' : '') . ')';
            $bindings[':ticket_like'] = ['%' . $ticket . '%', PDO::PARAM_STR];
            if ($ticketId > 0) {
                $bindings[':ticket_id'] = [$ticketId, PDO::PARAM_INT];
            }
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $countSql = '
            SELECT COUNT(*) AS total
            FROM ' . self::T_INCIDENCIA . ' i
            INNER JOIN ' . self::T_COOP . ' c
                ON c.' . self::COOP_ID . ' = i.' . self::COL_COOP . '
            ' . $whereSql . '
        ';

        try {
            $countRow = $this->db->fetch($countSql, $bindings);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar incidencias.', 0, $e);
        }

        $total = $countRow ? (int)$countRow['total'] : 0;
        if ($total === 0) {
            return [
                'items'   => [],
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        $sql = '
            SELECT
                i.' . self::COL_ID . ' AS id,
                i.' . self::COL_COOP . ' AS id_cooperativa,
                c.' . self::COOP_NOMBRE . ' AS cooperativa,
                i.' . self::COL_ASUNTO . ' AS asunto,
                i.' . self::COL_PRIOR . ' AS tipo_incidencia,
                i.' . self::COL_PRIOR . ' AS prioridad,
                i.' . self::COL_ESTADO . ' AS estado,
                i.' . self::COL_DESCRIP . ' AS descripcion,
                ' . $ticketExpr . ' AS ticket_codigo,
                COALESCE(i.' . self::COL_TICKET . ', i.' . self::COL_ID . ') AS ticket_numero,
                COALESCE(i.' . self::COL_CREATED . ', CURRENT_DATE) AS creado_en,
                contacto.oficial_nombre,
                contacto.oficial_correo,
                contacto.telefono_contacto,
                contacto.cargo,
                contacto.fecha_evento
            FROM ' . self::T_INCIDENCIA . ' i
            INNER JOIN ' . self::T_COOP . ' c
                ON c.' . self::COOP_ID . ' = i.' . self::COL_COOP . '
            LEFT JOIN LATERAL (
                SELECT
                    a.oficial_nombre,
                    a.oficial_correo,
                    a.telefono_contacto,
                    a.cargo,
                    COALESCE(a.fecha_evento, a.created_at) AS fecha_evento
                FROM ' . self::T_CONTACTOS . ' a
                WHERE a.' . self::CONTACT_COOP . ' = i.' . self::COL_COOP . '
                ORDER BY COALESCE(a.fecha_evento, a.created_at) DESC
                LIMIT 1
            ) contacto ON TRUE
            ' . $whereSql . '
            ORDER BY COALESCE(i.' . self::COL_CREATED . ', CURRENT_DATE) DESC, i.' . self::COL_ID . ' DESC
            LIMIT :limit OFFSET :offset
        ';

        $params = $bindings;
        $params[':limit']  = [$perPage, PDO::PARAM_INT];
        $params[':offset'] = [$offset, PDO::PARAM_INT];

        try {
            $items = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el listado de incidencias.', 0, $e);
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Crea una nueva incidencia y devuelve su identificador.
     *
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = '
            INSERT INTO ' . self::T_INCIDENCIA . ' (
                ' . self::COL_COOP . ',
                ' . self::COL_ASUNTO . ',
                ' . self::COL_DESCRIP . ',
                ' . self::COL_PRIOR . ',
                ' . self::COL_ESTADO . ',
                ' . self::COL_USER . '
            ) VALUES (
                :coop,
                :asunto,
                :descripcion,
                :prioridad,
                :estado,
                :usuario
            )
            RETURNING ' . self::COL_ID . '
        ';

        $descripcion = isset($data['descripcion']) ? trim((string)$data['descripcion']) : '';
        $descripcionParam = $descripcion === ''
            ? [null, PDO::PARAM_NULL]
            : [$descripcion, PDO::PARAM_STR];

        $params = [
            ':coop'        => [$data['id_cooperativa'] ?? 0, PDO::PARAM_INT],
            ':asunto'      => [$data['asunto'] ?? '', PDO::PARAM_STR],
            ':descripcion' => $descripcionParam,
            ':prioridad'   => [$data['tipo_incidencia'] ?? ($data['prioridad'] ?? ''), PDO::PARAM_STR],
            ':estado'      => [$data['estado'] ?? 'Enviado', PDO::PARAM_STR],
            ':usuario'     => [$data['creado_por'] ?? null, isset($data['creado_por']) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ];

        try {
            $row = $this->db->fetch($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo crear la incidencia.', 0, $e);
        }

        return $row ? (int)$row[self::COL_ID] : 0;
    }

    /**
     * Actualiza una incidencia existente.
     *
     * @param int $id
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $sql = '
            UPDATE ' . self::T_INCIDENCIA . '
            SET
                ' . self::COL_ASUNTO . ' = :asunto,
                ' . self::COL_PRIOR . ' = :prioridad,
                ' . self::COL_ESTADO . ' = :estado,
                ' . self::COL_DESCRIP . ' = :descripcion,
                ' . self::COL_UPDATED . ' = NOW()
            WHERE ' . self::COL_ID . ' = :id
        ';

        $descripcion = isset($data['descripcion']) ? trim((string)$data['descripcion']) : '';
        $descripcionParam = $descripcion === ''
            ? [null, PDO::PARAM_NULL]
            : [$descripcion, PDO::PARAM_STR];

        $params = [
            ':id'          => [$id, PDO::PARAM_INT],
            ':asunto'      => [$data['asunto'] ?? '', PDO::PARAM_STR],
            ':tipo'        => [$data['tipo_incidencia'] ?? '', PDO::PARAM_STR],
            ':prioridad'   => [$data['prioridad'] ?? '', PDO::PARAM_STR],
            ':estado'      => [$data['estado'] ?? 'Enviado', PDO::PARAM_STR],
            ':descripcion' => $descripcionParam,
        ];

        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo actualizar la incidencia.', 0, $e);
        }
    }

    /**
     * Elimina una incidencia.
     */
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::T_INCIDENCIA . ' WHERE ' . self::COL_ID . ' = :id';
        try {
            $this->db->execute($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo eliminar la incidencia.', 0, $e);
        }
    }

    /**
     * Obtiene el detalle de una incidencia junto con el último contacto registrado.
     */
    public function findWithContacto(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $ticketExpr = "CONCAT('INC-', TO_CHAR(COALESCE(i." . self::COL_CREATED . ", CURRENT_DATE), 'YYYY'), '-', LPAD(i." . self::COL_ID . "::text, 5, '0'))";

        $sql = '
            SELECT
                i.' . self::COL_ID . ' AS id,
                i.' . self::COL_COOP . ' AS id_cooperativa,
                c.' . self::COOP_NOMBRE . ' AS cooperativa,
                i.' . self::COL_ASUNTO . ' AS asunto,
                i.' . self::COL_PRIOR . ' AS tipo_incidencia,
                i.' . self::COL_PRIOR . ' AS prioridad,
                i.' . self::COL_ESTADO . ' AS estado,
                i.' . self::COL_DESCRIP . ' AS descripcion,
                ' . $ticketExpr . ' AS ticket_codigo,
                COALESCE(i.' . self::COL_TICKET . ', i.' . self::COL_ID . ') AS ticket_numero,
                COALESCE(i.' . self::COL_CREATED . ', CURRENT_DATE) AS creado_en,
                contacto.oficial_nombre,
                contacto.oficial_correo,
                contacto.telefono_contacto,
                contacto.cargo,
                contacto.fecha_evento
            FROM ' . self::T_INCIDENCIA . ' i
            INNER JOIN ' . self::T_COOP . ' c
                ON c.' . self::COOP_ID . ' = i.' . self::COL_COOP . '
            LEFT JOIN LATERAL (
                SELECT
                    a.oficial_nombre,
                    a.oficial_correo,
                    a.telefono_contacto,
                    a.cargo,
                    COALESCE(a.fecha_evento, a.created_at) AS fecha_evento
                FROM ' . self::T_CONTACTOS . ' a
                WHERE a.' . self::CONTACT_COOP . ' = i.' . self::COL_COOP . '
                ORDER BY COALESCE(a.fecha_evento, a.created_at) DESC
                LIMIT 1
            ) contacto ON TRUE
            WHERE i.' . self::COL_ID . ' = :id
        ';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener la incidencia.', 0, $e);
        }

        return $row ?: null;
    }

    /**
     * Devuelve cooperativas disponibles para los selectores.
     *
     * @return array<int,array{id:int,nombre:string}>
     */
    public function listadoCooperativas(): array
    {
        $sql = '
            SELECT ' . self::COOP_ID . ' AS id, ' . self::COOP_NOMBRE . ' AS nombre
            FROM ' . self::T_COOP . '
            ORDER BY ' . self::COOP_NOMBRE . '
        ';

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el listado de cooperativas.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['nombre'])) {
                continue;
            }
            $items[] = [
                'id'     => (int)$row['id'],
                'nombre' => (string)$row['nombre'],
            ];
        }

        return $items;
    }

    /**
     * Catálogo de incidencias generales.
     *
     * @return array<int,string>
     */
    public function catalogoIncidencias(): array
    {
        return [
            'Falla técnica en plataforma',
            'Solicitud de integración',
            'Problemas de acceso',
            'Actualización de datos',
            'Consulta operativa',
        ];
    }

    /**
     * Catálogo de prioridades permitidas.
     *
     * @return array<int,string>
     */
    public function catalogoPrioridades(): array
    {
        return ['Crítico', 'Alto', 'Medio', 'Bajo'];
    }

    /**
     * Catálogo de estados disponibles.
     *
     * @return array<int,string>
     */
    public function catalogoEstados(): array
    {
        return ['Enviado', 'Completado', 'Cancelado'];
    }
}
