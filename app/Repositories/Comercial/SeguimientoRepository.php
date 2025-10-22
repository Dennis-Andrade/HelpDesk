<?php
namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;
use Throwable;

final class SeguimientoRepository extends BaseRepository
{
    private const TABLE = 'public.comercial_seguimientos';
    private const COOPS_TABLE = 'public.cooperativas';
    private const CONTACTS_TABLE = 'public.contactos_cooperativa';
    private const TICKETS_VIEW = 'public.v_tickets_busqueda';
    private const USERS_TABLE = 'public.usuarios';
    private const TIPOS_TABLE = 'public.seguimiento_tipos';

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(60, $perPage));
        $offset = ($page - 1) * $perPage;

        $params = [];
        $where = $this->buildFilters($filters, $params);

        $joins = ' INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = s.id_cooperativa'
            . ' LEFT JOIN ' . self::USERS_TABLE . ' u ON u.id_usuario = s.creado_por'
            . ' LEFT JOIN ' . self::CONTACTS_TABLE . ' cc ON cc.id_contacto = s.id_contacto'
            . ' LEFT JOIN ' . self::TICKETS_VIEW . ' vt ON vt.id_ticket = s.ticket_id';

        $countSql = 'SELECT COUNT(*) AS total FROM ' . self::TABLE . ' s' . $joins . ($where !== '' ? ' ' . $where : '');

        try {
            $countRow = $this->db->fetch($countSql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('Error al contar los seguimientos.', 0, $e);
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

        $params[':limit'] = [$perPage, PDO::PARAM_INT];
        $params[':offset'] = [$offset, PDO::PARAM_INT];

        $select = $this->selectClause();
        $sql = 'SELECT ' . $select
            . ' FROM ' . self::TABLE . ' s'
            . $joins
            . ($where !== '' ? ' ' . $where : '')
            . ' ORDER BY s.fecha_actividad DESC, s.id DESC'
            . ' LIMIT :limit OFFSET :offset';

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('Error al obtener el historial de seguimientos.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }
    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listarParaExportar(array $filters): array
    {
        $params = [];
        $where = $this->buildFilters($filters, $params);

        $sql = 'SELECT ' . $this->selectClause()
            . ' FROM ' . self::TABLE . ' s'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = s.id_cooperativa'
            . ' LEFT JOIN ' . self::USERS_TABLE . ' u ON u.id_usuario = s.creado_por'
            . ' LEFT JOIN ' . self::CONTACTS_TABLE . ' cc ON cc.id_contacto = s.id_contacto'
            . ' LEFT JOIN ' . self::TICKETS_VIEW . ' vt ON vt.id_ticket = s.ticket_id'
            . ($where !== '' ? ' ' . $where : '')
            . ' ORDER BY s.fecha_actividad DESC, s.id DESC';

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('Error al exportar el historial de seguimientos.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,perPage:int}
     */
    public function agrupadoPorEntidad(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $params = [];
        $where  = $this->buildFilters($filters, $params);

        $cte = 'WITH filtered AS (
            SELECT
                s.id,
                s.id_cooperativa,
                s.tipo,
                s.descripcion,
                s.fecha_actividad,
                s.fecha_finalizacion,
                s.ticket_id,
                c.nombre AS cooperativa
            FROM ' . self::TABLE . ' s
            INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = s.id_cooperativa
            LEFT JOIN ' . self::TICKETS_VIEW . ' vt ON vt.id_ticket = s.ticket_id
            ' . ($where !== '' ? $where : '') . '
        )';

        $countSql = $cte . ' SELECT COUNT(DISTINCT id_cooperativa) AS total FROM filtered';

        try {
            $countRow = $this->db->fetch($countSql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar entidades para el seguimiento.', 0, $e);
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

        $querySql = $cte . '
            SELECT
                grouped.id_cooperativa,
                grouped.cooperativa,
                grouped.total,
                grouped.ultima_fecha,
                (
                    SELECT json_agg(row_to_json(sub) ORDER BY sub.fecha_actividad DESC, sub.id DESC)
                    FROM (
                        SELECT
                            f2.id,
                            f2.fecha_actividad,
                            f2.fecha_finalizacion,
                            f2.tipo,
                            f2.descripcion,
                            f2.ticket_id
                        FROM filtered f2
                        WHERE f2.id_cooperativa = grouped.id_cooperativa
                        ORDER BY f2.fecha_actividad DESC, f2.id DESC
                        LIMIT 5
                    ) AS sub
                ) AS seguimientos
            FROM (
                SELECT
                    f.id_cooperativa,
                    MAX(f.fecha_actividad) AS ultima_fecha,
                    COUNT(*) AS total,
                    MAX(f.cooperativa) AS cooperativa
                FROM filtered f
                GROUP BY f.id_cooperativa
            ) AS grouped
            ORDER BY grouped.ultima_fecha DESC
            LIMIT :limit OFFSET :offset';

        $queryParams = $params;
        $queryParams[':limit'] = [$perPage, PDO::PARAM_INT];
        $queryParams[':offset'] = [$offset, PDO::PARAM_INT];

        try {
            $rows = $this->db->fetchAll($querySql, $queryParams);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el seguimiento agrupado por entidad.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $seguimientos = [];
            if (!empty($row['seguimientos'])) {
                $decoded = json_decode((string)$row['seguimientos'], true);
                if (is_array($decoded)) {
                    $seguimientos = array_map(function ($item) {
                        return [
                            'id' => isset($item['id']) ? (int)$item['id'] : 0,
                            'fecha' => isset($item['fecha_actividad']) ? (string)$item['fecha_actividad'] : null,
                            'fecha_fin' => isset($item['fecha_finalizacion']) ? (string)$item['fecha_finalizacion'] : null,
                            'tipo' => isset($item['tipo']) ? (string)$item['tipo'] : null,
                            'descripcion' => isset($item['descripcion']) ? (string)$item['descripcion'] : null,
                            'ticket_id' => isset($item['ticket_id']) ? (int)$item['ticket_id'] : null,
                        ];
                    }, $decoded);
                }
            }

            $items[] = [
                'id_cooperativa' => isset($row['id_cooperativa']) ? (int)$row['id_cooperativa'] : 0,
                'cooperativa'    => isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
                'total'          => isset($row['total']) ? (int)$row['total'] : 0,
                'ultima_fecha'   => isset($row['ultima_fecha']) ? (string)$row['ultima_fecha'] : null,
                'seguimientos'   => $seguimientos,
            ];
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function eventosCalendario(string $desde, string $hasta): array
    {
        $sql = 'SELECT
                    s.id,
                    s.id_cooperativa,
                    c.nombre AS cooperativa,
                    s.tipo,
                    s.descripcion,
                    s.fecha_actividad,
                    s.fecha_finalizacion
                FROM ' . self::TABLE . ' s
                INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = s.id_cooperativa
                WHERE s.fecha_actividad BETWEEN :desde AND :hasta
                ORDER BY s.fecha_actividad ASC, s.id ASC';

        $params = [
            ':desde' => [$desde, PDO::PARAM_STR],
            ':hasta' => [$hasta, PDO::PARAM_STR],
        ];

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudieron obtener los seguimientos para el calendario.', 0, $e);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                id_cooperativa,
                fecha_actividad,
                fecha_finalizacion,
                tipo,
                descripcion,
                id_contacto,
                datos_reunion,
                datos_ticket,
                ticket_id,
                creado_por,
                created_at
            ) VALUES (
                :id_cooperativa,
                :fecha_inicio,
                :fecha_fin,
                :tipo,
                :descripcion,
                :id_contacto,
                :datos_reunion,
                :datos_ticket,
                :ticket_id,
                :creado_por,
                NOW()
            ) RETURNING id';

        $params = $this->buildPersistenceParams($data, false);

        try {
            $result = $this->db->execute($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo registrar el seguimiento.', 0, $e);
        }

        $row = is_array($result) && isset($result[0]) ? $result[0] : null;
        return $row && isset($row['id']) ? (int)$row['id'] : 0;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET
                id_cooperativa = :id_cooperativa,
                fecha_actividad = :fecha_inicio,
                fecha_finalizacion = :fecha_fin,
                tipo = :tipo,
                descripcion = :descripcion,
                id_contacto = :id_contacto,
                datos_reunion = :datos_reunion,
                datos_ticket = :datos_ticket,
                ticket_id = :ticket_id,
                editado_por = :usuario_editor,
                editado_en = NOW()
            WHERE id = :id';

        $params = $this->buildPersistenceParams($data, true);
        $params[':id'] = [$id, PDO::PARAM_INT];

        try {
            $this->db->execute($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo actualizar el seguimiento.', 0, $e);
        }
    }
    public function find(int $id): ?array
    {
        $sql = 'SELECT ' . $this->selectClause()
            . ' FROM ' . self::TABLE . ' s'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = s.id_cooperativa'
            . ' LEFT JOIN ' . self::USERS_TABLE . ' u ON u.id_usuario = s.creado_por'
            . ' LEFT JOIN ' . self::CONTACTS_TABLE . ' cc ON cc.id_contacto = s.id_contacto'
            . ' LEFT JOIN ' . self::TICKETS_VIEW . ' vt ON vt.id_ticket = s.ticket_id'
            . ' WHERE s.id = :id';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el seguimiento solicitado.', 0, $e);
        }

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @return array<int,array{id:int,nombre:string}>
     */
    public function listadoCooperativas(): array
    {
        $sql = 'SELECT id_cooperativa AS id, nombre FROM ' . self::COOPS_TABLE . ' WHERE activa = true ORDER BY nombre ASC';

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudieron obtener las entidades.', 0, $e);
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
     * @return array<int,string>
     */
    public function catalogoTipos(): array
    {
        $sql = 'SELECT nombre FROM ' . self::TIPOS_TABLE . ' WHERE nombre <> :omit ORDER BY orden ASC, nombre ASC';

        try {
            $rows = $this->db->fetchAll($sql, [':omit' => ['Seguimiento', PDO::PARAM_STR]]);
        } catch (Throwable $e) {
            return ['Contacto', 'Reunión', 'Ticket'];
        }

        $tipos = [];
        foreach ($rows as $row) {
            if (!isset($row['nombre'])) {
                continue;
            }
            $value = trim((string)$row['nombre']);
            if ($value === '' || strcasecmp($value, 'Seguimiento') === 0) {
                continue;
            }
            $tipos[] = $value;
        }

        if (!$tipos) {
            $tipos = ['Contacto', 'Reunión', 'Ticket'];
        }

        return array_values(array_unique($tipos));
    }

    /**
     * @return array<int,array{id:int,nombre:string,telefono:?string,email:?string}>
     */
    public function contactosPorEntidad(int $entidadId): array
    {
        if ($entidadId <= 0) {
            return [];
        }

        $sql = 'SELECT id_contacto AS id, nombre_contacto AS nombre, telefono, email'
            . ' FROM ' . self::CONTACTS_TABLE
            . ' WHERE id_cooperativa = :id AND activo = true'
            . ' ORDER BY nombre_contacto ASC';

        try {
            $rows = $this->db->fetchAll($sql, [':id' => [$entidadId, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudieron obtener los contactos de la entidad.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['nombre'])) {
                continue;
            }
            $items[] = [
                'id'       => (int)$row['id'],
                'nombre'   => (string)$row['nombre'],
                'telefono' => isset($row['telefono']) ? (string)$row['telefono'] : null,
                'email'    => isset($row['email']) ? (string)$row['email'] : null,
            ];
        }

        return $items;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function buscarTickets(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $sql = 'SELECT '
            . ' vt.id_ticket,'
            . ' vt.codigo_ticket AS codigo,'
            . ' vt.titulo,'
            . ' vt.descripcion,'
            . ' vt.departamento_id,'
            . ' vt.departamento_nombre,'
            . ' vt.nombre_categoria,'
            . ' vt.prioridad,'
            . ' vt.estado'
            . ' FROM ' . self::TICKETS_VIEW . ' vt'
            . ' WHERE (vt.titulo ILIKE :term'
            . ' OR vt.codigo_ticket ILIKE :term)'
            . ' ORDER BY vt.id_ticket DESC'
            . ' LIMIT 10';

        try {
            $rows = $this->db->fetchAll($sql, [':term' => ['%' . $term . '%', PDO::PARAM_STR]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo buscar tickets.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['id_ticket'])) {
                continue;
            }
            $codigo = isset($row['codigo']) ? (string)$row['codigo'] : '';
            if ($codigo === '') {
                $codigo = 'Ticket #' . (int)$row['id_ticket'];
            }
            $titulo = isset($row['titulo']) ? trim((string)$row['titulo']) : '';
            if ($titulo === '') {
                $detalle = isset($row['descripcion']) ? trim((string)$row['descripcion']) : '';
                if ($detalle !== '') {
                    $substrFn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
                    $lengthFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                    $resumen = $substrFn($detalle, 0, 80);
                    if ($lengthFn($detalle) > 80) {
                        $resumen .= '…';
                    }
                    $titulo = $resumen;
                } else {
                    $titulo = 'Incidencia #' . (int)$row['id_ticket'];
                }
            }

            $items[] = [
                'id'          => (int)$row['id_ticket'],
                'id_ticket'   => (int)$row['id_ticket'],
                'codigo'      => $codigo,
                'titulo'      => $titulo,
                'departamento_id' => isset($row['departamento_id']) ? (int)$row['departamento_id'] : null,
                'departamento'=> isset($row['departamento_nombre']) ? (string)$row['departamento_nombre'] : '',
                'tipo'        => isset($row['nombre_categoria']) ? (string)$row['nombre_categoria'] : '',
                'prioridad'   => isset($row['prioridad']) ? (string)$row['prioridad'] : '',
                'estado'      => isset($row['estado']) ? (string)$row['estado'] : '',
            ];
        }

        return $items;
    }
    /**
     * @return array<string,mixed>|null
     */
    public function ticketPorId(int $ticketId): ?array
    {
        if ($ticketId <= 0) {
            return null;
        }

        $sql = 'SELECT '
            . ' vt.id_ticket,'
            . ' vt.codigo_ticket AS codigo,'
            . ' vt.titulo,'
            . ' vt.descripcion,'
            . ' vt.departamento_id,'
            . ' vt.departamento_nombre,'
            . ' vt.nombre_categoria,'
            . ' vt.prioridad,'
            . ' vt.estado'
            . ' FROM ' . self::TICKETS_VIEW . ' vt'
            . ' WHERE vt.id_ticket = :id'
            . ' LIMIT 1';

        try {
            $row = $this->db->fetch($sql, [':id' => [$ticketId, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener la información del ticket.', 0, $e);
        }

        if (!$row) {
            return null;
        }

        $codigo = isset($row['codigo']) ? (string)$row['codigo'] : '';
        if ($codigo === '') {
            $codigo = 'Ticket #' . (int)$row['id_ticket'];
        }

        $titulo = isset($row['titulo']) ? trim((string)$row['titulo']) : '';
        if ($titulo === '') {
            $detalle = isset($row['descripcion']) ? trim((string)$row['descripcion']) : '';
            if ($detalle !== '') {
                $substrFn = function_exists('mb_substr') ? 'mb_substr' : 'substr';
                $lengthFn = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                $resumen = $substrFn($detalle, 0, 80);
                if ($lengthFn($detalle) > 80) {
                    $resumen .= '…';
                }
                $titulo = $resumen;
            } else {
                $titulo = 'Incidencia #' . (int)$row['id_ticket'];
            }
        }

        return [
            'id'          => (int)$row['id_ticket'],
            'id_ticket'   => (int)$row['id_ticket'],
            'codigo'      => $codigo,
            'titulo'      => $titulo,
            'departamento_id' => isset($row['departamento_id']) ? (int)$row['departamento_id'] : null,
            'departamento'=> isset($row['departamento_nombre']) ? (string)$row['departamento_nombre'] : '',
            'tipo'        => isset($row['nombre_categoria']) ? (string)$row['nombre_categoria'] : '',
            'prioridad'   => isset($row['prioridad']) ? (string)$row['prioridad'] : '',
            'estado'      => isset($row['estado']) ? (string)$row['estado'] : '',
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function buscarTicketsSeguimiento(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $codigoExpr = $this->incidentCodeExpression('ic');
        $sql = 'SELECT DISTINCT'
            . ' s.ticket_id,'
            . " COALESCE(s.datos_ticket->>'codigo', vt.codigo_ticket, '') AS codigo,"
            . ' LEFT(COALESCE(s.datos_ticket->>\'descripcion\', vt.descripcion, s.descripcion), 120) AS descripcion'
            . ' FROM ' . self::TABLE . ' s'
            . ' LEFT JOIN ' . self::TICKETS_VIEW . ' vt ON vt.id_ticket = s.ticket_id'
            . ' WHERE ('
            . ' (s.ticket_id IS NOT NULL AND CAST(s.ticket_id AS TEXT) ILIKE :term)'
            . " OR (COALESCE(s.datos_ticket->>'codigo', vt.codigo_ticket, '') ILIKE :term)"
            . ' OR (s.descripcion ILIKE :term)'
            . ' )'
            . ' ORDER BY COALESCE(s.ticket_id, 0) DESC, codigo DESC, s.fecha_actividad DESC'
            . ' LIMIT 10';

        try {
            $rows = $this->db->fetchAll($sql, [':term' => ['%' . $term . '%', PDO::PARAM_STR]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudieron obtener los tickets registrados en seguimiento.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $ticketId = isset($row['ticket_id']) ? (int)$row['ticket_id'] : 0;
            $codigo = isset($row['codigo']) ? (string)$row['codigo'] : '';
            if ($codigo === '' && $ticketId > 0) {
                $codigo = 'Ticket #' . $ticketId;
            }
            $items[] = [
                'id'          => $ticketId,
                'ticket_id'   => $ticketId,
                'codigo'      => $codigo,
                'descripcion' => isset($row['descripcion']) ? (string)$row['descripcion'] : '',
            ];
        }

        return $items;
    }

    public function delete(int $id): void
    {
        if ($id <= 0) {
            throw new RuntimeException('El identificador del seguimiento no es válido.');
        }

        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = :id';

        try {
            $this->db->execute($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo eliminar el seguimiento.', 0, $e);
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,array{0:mixed,1:int}> $params
     */
    private function buildFilters(array $filters, array &$params): string
    {
        $conditions = [];

        $fecha = isset($filters['fecha']) ? trim((string)$filters['fecha']) : '';
        if ($fecha !== '') {
            $conditions[] = 'DATE(s.fecha_actividad) = :fecha';
            $params[':fecha'] = [$fecha, PDO::PARAM_STR];
        } else {
            $desde = isset($filters['desde']) ? trim((string)$filters['desde']) : '';
            if ($desde !== '') {
                $conditions[] = 'DATE(s.fecha_actividad) >= :desde';
                $params[':desde'] = [$desde, PDO::PARAM_STR];
            }
            $hasta = isset($filters['hasta']) ? trim((string)$filters['hasta']) : '';
            if ($hasta !== '') {
                $conditions[] = 'DATE(s.fecha_actividad) <= :hasta';
                $params[':hasta'] = [$hasta, PDO::PARAM_STR];
            }
        }

        $coop = isset($filters['coop']) ? (int)$filters['coop'] : 0;
        if ($coop > 0) {
            $conditions[] = 's.id_cooperativa = :coop';
            $params[':coop'] = [$coop, PDO::PARAM_INT];
        }

        $tipo = isset($filters['tipo']) ? trim((string)$filters['tipo']) : '';
        if ($tipo !== '') {
            $conditions[] = 's.tipo = :tipo';
            $params[':tipo'] = [$tipo, PDO::PARAM_STR];
        }

        $ticket = isset($filters['ticket']) ? trim((string)$filters['ticket']) : '';
        if ($ticket !== '') {
            $conditions[] = '((CAST(s.ticket_id AS TEXT) ILIKE :ticket)'
                . ' OR (COALESCE(s.datos_ticket::text, \'\') ILIKE :ticket)'
                . ' OR (COALESCE(vt.codigo_ticket, \'\') ILIKE :ticket))';
            $params[':ticket'] = ['%' . $ticket . '%', PDO::PARAM_STR];
        }

        $texto = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($texto !== '') {
            $conditions[] = 's.descripcion ILIKE :texto';
            $params[':texto'] = ['%' . $texto . '%', PDO::PARAM_STR];
        }

        return $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }

    private function selectClause(): string
    {
        return implode(', ', [
            's.id',
            's.id_cooperativa',
            'c.nombre AS cooperativa',
            's.fecha_actividad::date AS fecha_actividad',
            's.fecha_finalizacion::date AS fecha_finalizacion',
            's.tipo',
            's.descripcion',
            's.id_contacto',
            's.datos_reunion',
            's.datos_ticket',
            's.ticket_id',
            's.creado_por',
            's.created_at::date AS created_at',
            's.editado_por',
            's.editado_en::date AS editado_en',
            "COALESCE(u.nombre_completo, u.username, '') AS usuario_nombre",
            'u.id_usuario AS usuario_id',
            'cc.nombre_contacto',
            'cc.telefono AS contacto_telefono',
            'cc.email AS contacto_email',
            'COALESCE(vt.codigo_ticket, \'\') AS codigo_ticket',
            'COALESCE(vt.departamento_nombre, \'\') AS departamento_nombre',
            'COALESCE(vt.nombre_categoria, \'\') AS nombre_categoria',
            'vt.prioridad',
            'vt.estado'
        ]);
    }
    /**
     * @param array<string,mixed> $data
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildPersistenceParams(array $data, bool $forUpdate): array
    {
        $params = [
            ':id_cooperativa' => [$data['id_cooperativa'] ?? 0, PDO::PARAM_INT],
            ':fecha_inicio'   => [$data['fecha_inicio'] ?? null, $this->paramType($data['fecha_inicio'] ?? null)],
            ':fecha_fin'      => [$data['fecha_fin'] ?? null, $this->paramType($data['fecha_fin'] ?? null)],
            ':tipo'           => [$data['tipo'] ?? '', PDO::PARAM_STR],
            ':descripcion'    => [$data['descripcion'] ?? '', PDO::PARAM_STR],
            ':id_contacto'    => [$data['id_contacto'] ?? null, $this->paramType($data['id_contacto'] ?? null, true)],
            ':datos_reunion'  => [$data['datos_reunion'] ?? null, $this->paramType($data['datos_reunion'] ?? null)],
            ':datos_ticket'   => [$data['datos_ticket'] ?? null, $this->paramType($data['datos_ticket'] ?? null)],
            ':ticket_id'      => [$data['ticket_id'] ?? null, $this->paramType($data['ticket_id'] ?? null, true)],
        ];

        if ($forUpdate) {
            $params[':usuario_editor'] = [$data['usuario_editor'] ?? null, $this->paramType($data['usuario_editor'] ?? null, true)];
        } else {
            $params[':creado_por'] = [$data['creado_por'] ?? null, $this->paramType($data['creado_por'] ?? null, true)];
        }

        return $params;
    }

    private function paramType($value, bool $isInt = false): int
    {
        if ($value === null || $value === '') {
            return PDO::PARAM_NULL;
        }
        return $isInt ? PDO::PARAM_INT : PDO::PARAM_STR;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapRow(array $row): array
    {
        $fechaInicio = isset($row['fecha_actividad']) ? (string)$row['fecha_actividad'] : '';
        $fechaFin = isset($row['fecha_finalizacion']) ? (string)$row['fecha_finalizacion'] : '';

        $datosTicket = null;
        if (isset($row['datos_ticket']) && $row['datos_ticket'] !== null && $row['datos_ticket'] !== '') {
            $decoded = json_decode((string)$row['datos_ticket'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $datosTicket = $decoded;
            }
        }

        $datosReunion = null;
        if (isset($row['datos_reunion']) && $row['datos_reunion'] !== null && $row['datos_reunion'] !== '') {
            $decoded = json_decode((string)$row['datos_reunion'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $datosReunion = $decoded;
            }
        }

        $ticketCodigo = isset($row['codigo_ticket']) ? (string)$row['codigo_ticket'] : '';
        $ticketPrioridad = isset($row['prioridad']) ? (string)$row['prioridad'] : '';
        $ticketEstado = isset($row['estado']) ? (string)$row['estado'] : '';
        $ticketDepartamento = isset($row['departamento_nombre']) ? (string)$row['departamento_nombre'] : '';
        $ticketTipo = isset($row['nombre_categoria']) ? (string)$row['nombre_categoria'] : '';

        if ($datosTicket === null && $ticketCodigo !== '') {
            $datosTicket = [
                'codigo'       => $ticketCodigo,
                'departamento' => $ticketDepartamento,
                'tipo'         => $ticketTipo,
                'prioridad'    => $ticketPrioridad,
                'estado'       => $ticketEstado,
            ];
        }

        $usuarioNombre = isset($row['usuario_nombre']) ? trim((string)$row['usuario_nombre']) : '';
        $usuarioId = isset($row['usuario_id']) ? (int)$row['usuario_id'] : 0;
        if ($usuarioNombre === '' && $usuarioId > 0) {
            $usuarioNombre = 'Usuario #' . $usuarioId;
        }

        return [
            'id'                   => isset($row['id']) ? (int)$row['id'] : 0,
            'id_cooperativa'       => isset($row['id_cooperativa']) ? (int)$row['id_cooperativa'] : 0,
            'cooperativa'          => isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
            'fecha_inicio'         => $fechaInicio,
            'fecha_fin'            => $fechaFin,
            'tipo'                 => isset($row['tipo']) ? (string)$row['tipo'] : '',
            'descripcion'          => isset($row['descripcion']) ? (string)$row['descripcion'] : '',
            'id_contacto'          => isset($row['id_contacto']) ? (int)$row['id_contacto'] : null,
            'contacto_nombre'      => isset($row['nombre_contacto']) ? (string)$row['nombre_contacto'] : '',
            'contacto_telefono'    => isset($row['contacto_telefono']) ? (string)$row['contacto_telefono'] : '',
            'contacto_email'       => isset($row['contacto_email']) ? (string)$row['contacto_email'] : '',
            'ticket_id'            => isset($row['ticket_id']) ? (int)$row['ticket_id'] : null,
            'ticket_codigo'        => $ticketCodigo,
            'ticket_departamento'  => $ticketDepartamento,
            'ticket_tipo'          => $ticketTipo,
            'ticket_prioridad'     => $ticketPrioridad,
            'ticket_estado'        => $ticketEstado,
            'datos_ticket'         => $datosTicket,
            'datos_reunion'        => $datosReunion,
            'creado_en'            => isset($row['created_at']) ? (string)$row['created_at'] : '',
            'usuario'              => $usuarioNombre,
            'usuario_id'           => $usuarioId,
            'editado_en'           => isset($row['editado_en']) ? (string)$row['editado_en'] : null,
        ];
    }

}
