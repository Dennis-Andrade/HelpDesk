<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;
use Throwable;

final class SeguimientoRepository extends BaseRepository
{
    private const TABLE             = 'public.contabilidad_seguimientos';
    private const COOPS_TABLE       = 'public.cooperativas';
    private const CONTACTS_TABLE    = 'public.contactos_cooperativa';
    private const TICKETS_TABLE     = 'public.contabilidad_tickets';
    private const CONTRATOS_TABLE   = 'public.contrataciones_servicios';
    private const USERS_TABLE       = 'public.usuarios';

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(5, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $params = [];
        $where  = $this->buildFilters($filters, $params);

        $joins = ' INNER JOIN ' . self::COOPS_TABLE . ' coop ON coop.id_cooperativa = seg.id_cooperativa'
            . ' LEFT JOIN ' . self::USERS_TABLE . ' usr ON usr.id_usuario = seg.creado_por'
            . ' LEFT JOIN ' . self::CONTACTS_TABLE . ' contact ON contact.id_contacto = seg.id_contacto'
            . ' LEFT JOIN ' . self::TICKETS_TABLE . ' tic ON tic.id = seg.ticket_id'
            . ' LEFT JOIN ' . self::CONTRATOS_TABLE . ' con ON con.id_contratacion = seg.id_contratacion';

        $countSql = 'SELECT COUNT(*) AS total FROM ' . self::TABLE . ' seg' . $joins . ($where !== '' ? ' ' . $where : '');

        try {
            $countRow = $this->db->fetch($countSql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('Error al contar el seguimiento contable.', 0, $e);
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

        $params[':limit']  = [$perPage, PDO::PARAM_INT];
        $params[':offset'] = [$offset, PDO::PARAM_INT];

        $select = $this->selectClause();
        $sql = 'SELECT ' . $select
            . ' FROM ' . self::TABLE . ' seg'
            . $joins
            . ($where !== '' ? ' ' . $where : '')
            . ' ORDER BY seg.fecha_actividad DESC, seg.id DESC'
            . ' LIMIT :limit OFFSET :offset';

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el seguimiento contable.', 0, $e);
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
        $where  = $this->buildFilters($filters, $params);

        $select = $this->selectClause();
        $sql = 'SELECT ' . $select
            . ' FROM ' . self::TABLE . ' seg'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' coop ON coop.id_cooperativa = seg.id_cooperativa'
            . ' LEFT JOIN ' . self::USERS_TABLE . ' usr ON usr.id_usuario = seg.creado_por'
            . ' LEFT JOIN ' . self::CONTACTS_TABLE . ' contact ON contact.id_contacto = seg.id_contacto'
            . ' LEFT JOIN ' . self::TICKETS_TABLE . ' tic ON tic.id = seg.ticket_id'
            . ' LEFT JOIN ' . self::CONTRATOS_TABLE . ' con ON con.id_contratacion = seg.id_contratacion'
            . ($where !== '' ? ' ' . $where : '')
            . ' ORDER BY seg.fecha_actividad DESC, seg.id DESC';

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo exportar el seguimiento contable.', 0, $e);
        }

        return array_map([$this, 'mapRow'], $rows);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function eventosCalendario(string $desde, string $hasta): array
    {
        $sql = 'SELECT
                    seg.id,
                    seg.id_cooperativa,
                    coop.nombre AS cooperativa,
                    seg.tipo,
                    seg.medio,
                    seg.descripcion,
                    seg.fecha_actividad,
                    seg.fecha_finalizacion
                FROM ' . self::TABLE . ' seg
                INNER JOIN ' . self::COOPS_TABLE . ' coop ON coop.id_cooperativa = seg.id_cooperativa
                WHERE seg.fecha_actividad BETWEEN :desde AND :hasta
                ORDER BY seg.fecha_actividad ASC, seg.id ASC';

        $params = [
            ':desde' => [$desde, PDO::PARAM_STR],
            ':hasta' => [$hasta, PDO::PARAM_STR],
        ];

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el seguimiento contable para el calendario.', 0, $e);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                id_cooperativa,
                id_contratacion,
                fecha_actividad,
                fecha_finalizacion,
                tipo,
                medio,
                descripcion,
                resultado,
                id_contacto,
                datos_reunion,
                datos_ticket,
                ticket_id,
                creado_por,
                created_at
            ) VALUES (
                :id_cooperativa,
                :id_contratacion,
                :fecha_inicio,
                :fecha_fin,
                :tipo,
                :medio,
                :descripcion,
                :resultado,
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
        return [
            'Conciliación',
            'Cobranza',
            'Declaración',
            'Facturación',
            'Retenciones',
            'Soporte',
            'Ticket',
        ];
    }

    /**
     * @return array<int,string>
     */
    public function catalogoMedios(): array
    {
        return ['Correo', 'Llamada', 'Reunión', 'Visita', 'Ticket', 'Otro'];
    }

    /**
     * @return array<int,string>
     */
    public function catalogoResultados(): array
    {
        return ['Pendiente', 'Escalado', 'Resuelto', 'Sin respuesta'];
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
            throw new RuntimeException('No se pudieron obtener los contactos.', 0, $e);
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

        $sql = 'SELECT id, codigo, asunto, categoria, prioridad, estado'
            . ' FROM ' . self::TICKETS_TABLE
            . ' WHERE codigo ILIKE :term OR asunto ILIKE :term'
            . ' ORDER BY id DESC LIMIT 10';

        try {
            $rows = $this->db->fetchAll($sql, [':term' => ['%' . $term . '%', PDO::PARAM_STR]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo buscar tickets contables.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['id'])) {
                continue;
            }
            $items[] = [
                'id'        => (int)$row['id'],
                'codigo'    => (string)($row['codigo'] ?? ''),
                'titulo'    => (string)($row['asunto'] ?? ''),
                'tipo'      => (string)($row['categoria'] ?? ''),
                'prioridad' => (string)($row['prioridad'] ?? ''),
                'estado'    => (string)($row['estado'] ?? ''),
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

        $sql = 'SELECT id, codigo, asunto, categoria, prioridad, estado'
            . ' FROM ' . self::TICKETS_TABLE
            . ' WHERE id = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, [':id' => [$ticketId, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el ticket contable.', 0, $e);
        }

        if (!$row) {
            return null;
        }

        return [
            'id'        => (int)$row['id'],
            'codigo'    => (string)($row['codigo'] ?? ''),
            'asunto'    => (string)($row['asunto'] ?? ''),
            'categoria' => (string)($row['categoria'] ?? ''),
            'prioridad' => (string)($row['prioridad'] ?? ''),
            'estado'    => (string)($row['estado'] ?? ''),
        ];
    }

    /**
     * @return array<int,array{id:int,codigo:string}>
     */
    public function contratosPorEntidad(int $entidadId): array
    {
        if ($entidadId <= 0) {
            return [];
        }

        $sql = 'SELECT id_contratacion AS id, COALESCE(codigo_referencia, ' .
            " 'CTR-' || TO_CHAR(fecha_contratacion, 'YYYY') || '-' || LPAD(id_contratacion::text, 4, '0')" .
            ') AS codigo'
            . ' FROM ' . self::CONTRATOS_TABLE
            . ' WHERE id_cooperativa = :id'
            . ' ORDER BY fecha_contratacion DESC, id_contratacion DESC';

        try {
            $rows = $this->db->fetchAll($sql, [':id' => [$entidadId, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudieron obtener los contratos.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['codigo'])) {
                continue;
            }
            $items[] = [
                'id'     => (int)$row['id'],
                'codigo' => (string)$row['codigo'],
            ];
        }

        return $items;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        $sql = 'SELECT ' . $this->selectClause()
            . ' FROM ' . self::TABLE . ' seg'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' coop ON coop.id_cooperativa = seg.id_cooperativa'
            . ' LEFT JOIN ' . self::USERS_TABLE . ' usr ON usr.id_usuario = seg.creado_por'
            . ' LEFT JOIN ' . self::CONTACTS_TABLE . ' contact ON contact.id_contacto = seg.id_contacto'
            . ' LEFT JOIN ' . self::TICKETS_TABLE . ' tic ON tic.id = seg.ticket_id'
            . ' LEFT JOIN ' . self::CONTRATOS_TABLE . ' con ON con.id_contratacion = seg.id_contratacion'
            . ' WHERE seg.id = :id';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el seguimiento solicitado.', 0, $e);
        }

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,array{0:mixed,1:int}> $params
     */
    private function buildFilters(array $filters, array &$params): string
    {
        $conditions = [];

        if (!empty($filters['coop'])) {
            $conditions[] = 'seg.id_cooperativa = :coop';
            $params[':coop'] = [(int)$filters['coop'], PDO::PARAM_INT];
        }

        if (!empty($filters['tipo'])) {
            $conditions[] = 'seg.tipo = :tipo';
            $params[':tipo'] = [$filters['tipo'], PDO::PARAM_STR];
        }

        if (!empty($filters['medio'])) {
            $conditions[] = 'seg.medio = :medio';
            $params[':medio'] = [$filters['medio'], PDO::PARAM_STR];
        }

        if (!empty($filters['resultado'])) {
            $conditions[] = 'seg.resultado = :resultado';
            $params[':resultado'] = [$filters['resultado'], PDO::PARAM_STR];
        }

        if (!empty($filters['desde'])) {
            $conditions[] = 'seg.fecha_actividad >= :desde';
            $params[':desde'] = [$filters['desde'], PDO::PARAM_STR];
        }

        if (!empty($filters['hasta'])) {
            $conditions[] = 'seg.fecha_actividad <= :hasta';
            $params[':hasta'] = [$filters['hasta'], PDO::PARAM_STR];
        }

        if (!empty($filters['ticket'])) {
            $conditions[] = '(tic.codigo ILIKE :ticket OR tic.asunto ILIKE :ticket)';
            $params[':ticket'] = ['%' . $filters['ticket'] . '%', PDO::PARAM_STR];
        }

        if (!empty($filters['q'])) {
            $conditions[] = '(seg.descripcion ILIKE :q OR seg.resultado ILIKE :q)';
            $params[':q'] = ['%' . $filters['q'] . '%', PDO::PARAM_STR];
        }

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function selectClause(): string
    {
        return implode(', ', [
            'seg.id',
            'seg.id_cooperativa',
            'seg.id_contratacion',
            'seg.fecha_actividad',
            'seg.fecha_finalizacion',
            'seg.tipo',
            'seg.medio',
            'seg.descripcion',
            'seg.resultado',
            'seg.id_contacto',
            'seg.datos_ticket',
            'seg.ticket_id',
            'seg.creado_por',
            'seg.created_at',
            'seg.editado_por',
            'seg.editado_en',
            'coop.nombre AS cooperativa',
            'contact.nombre_contacto AS contacto_nombre',
            'contact.telefono AS contacto_telefono',
            'contact.email AS contacto_email',
            'COALESCE(tic.codigo, \' \') AS ticket_codigo',
            'tic.estado AS ticket_estado',
            'tic.prioridad AS ticket_prioridad',
            'con.fecha_contratacion',
            'con.codigo_referencia',
            'COALESCE(usr.nombre, usr.username) AS usuario',
        ]);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapRow(array $row): array
    {
        $ticketDatos = null;
        if (isset($row['datos_ticket'])) {
            if (is_array($row['datos_ticket'])) {
                $ticketDatos = $row['datos_ticket'];
            } else {
                $decoded = json_decode((string)$row['datos_ticket'], true);
                $ticketDatos = is_array($decoded) ? $decoded : null;
            }
        }

        return [
            'id'                => isset($row['id']) ? (int)$row['id'] : 0,
            'id_cooperativa'    => isset($row['id_cooperativa']) ? (int)$row['id_cooperativa'] : 0,
            'id_contratacion'   => isset($row['id_contratacion']) && $row['id_contratacion'] !== null ? (int)$row['id_contratacion'] : null,
            'cooperativa'       => isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
            'fecha_inicio'      => isset($row['fecha_actividad']) ? (string)$row['fecha_actividad'] : null,
            'fecha_fin'         => isset($row['fecha_finalizacion']) ? (string)$row['fecha_finalizacion'] : null,
            'tipo'              => isset($row['tipo']) ? (string)$row['tipo'] : '',
            'medio'             => isset($row['medio']) ? (string)$row['medio'] : '',
            'descripcion'       => isset($row['descripcion']) ? (string)$row['descripcion'] : '',
            'resultado'         => isset($row['resultado']) ? (string)$row['resultado'] : '',
            'id_contacto'       => isset($row['id_contacto']) && $row['id_contacto'] !== null ? (int)$row['id_contacto'] : null,
            'contacto_nombre'   => isset($row['contacto_nombre']) ? (string)$row['contacto_nombre'] : null,
            'contacto_telefono' => isset($row['contacto_telefono']) ? (string)$row['contacto_telefono'] : null,
            'contacto_email'    => isset($row['contacto_email']) ? (string)$row['contacto_email'] : null,
            'ticket_id'         => isset($row['ticket_id']) && $row['ticket_id'] !== null ? (int)$row['ticket_id'] : null,
            'ticket_codigo'     => isset($row['ticket_codigo']) ? trim((string)$row['ticket_codigo']) : null,
            'ticket_estado'     => isset($row['ticket_estado']) ? (string)$row['ticket_estado'] : null,
            'ticket_prioridad'  => isset($row['ticket_prioridad']) ? (string)$row['ticket_prioridad'] : null,
            'datos_ticket'      => $ticketDatos,
            'usuario'           => isset($row['usuario']) ? (string)$row['usuario'] : null,
            'creado_por'        => isset($row['creado_por']) ? (int)$row['creado_por'] : null,
            'created_at'        => isset($row['created_at']) ? (string)$row['created_at'] : null,
            'editado_por'       => isset($row['editado_por']) ? (int)$row['editado_por'] : null,
            'editado_en'        => isset($row['editado_en']) ? (string)$row['editado_en'] : null,
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildPersistenceParams(array $data, bool $forUpdate): array
    {
        return [
            ':id_cooperativa'  => [$data['id_cooperativa'], PDO::PARAM_INT],
            ':id_contratacion' => [$data['id_contratacion'] ?? null, $this->nullableInt($data['id_contratacion'] ?? null)],
            ':fecha_inicio'    => [$data['fecha_inicio'], PDO::PARAM_STR],
            ':fecha_fin'       => [$data['fecha_fin'] ?? null, $this->nullableStr($data['fecha_fin'] ?? null)],
            ':tipo'            => [$data['tipo'], PDO::PARAM_STR],
            ':medio'           => [$data['medio'] ?? null, $this->nullableStr($data['medio'] ?? null)],
            ':descripcion'     => [$data['descripcion'], PDO::PARAM_STR],
            ':resultado'       => [$data['resultado'] ?? null, $this->nullableStr($data['resultado'] ?? null)],
            ':id_contacto'     => [$data['id_contacto'] ?? null, $this->nullableInt($data['id_contacto'] ?? null)],
            ':datos_reunion'   => [$data['datos_reunion'] ?? null, $this->nullableStr($data['datos_reunion'] ?? null)],
            ':datos_ticket'    => [$data['datos_ticket'] ?? null, $this->nullableStr($data['datos_ticket'] ?? null)],
            ':ticket_id'       => [$data['ticket_id'] ?? null, $this->nullableInt($data['ticket_id'] ?? null)],
            ':creado_por'      => [$data['creado_por'] ?? null, $this->nullableInt($data['creado_por'] ?? null)],
            ':usuario_editor'  => [$data['usuario_editor'] ?? null, $this->nullableInt($data['usuario_editor'] ?? null)],
        ];
    }

    private function nullableInt($value): int
    {
        return ($value === null || $value === '') ? PDO::PARAM_NULL : PDO::PARAM_INT;
    }

    private function nullableStr($value): int
    {
        return ($value === null || $value === '') ? PDO::PARAM_NULL : PDO::PARAM_STR;
    }
}
