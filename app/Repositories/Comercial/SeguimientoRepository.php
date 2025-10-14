<?php
namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

final class SeguimientoRepository extends BaseRepository
{
    private const TABLE_CANDIDATES = [
        'public.comercial_seguimientos',
        'public.seguimientos_comercial',
        'public.seguimientos_diarios',
        'public.seguimiento_diario',
        'public.seguimientos',
    ];

    private const TIPOS_TABLE_CANDIDATES = [
        'public.seguimiento_tipos',
        'public.tipos_seguimiento',
        'public.tipos_seguimientos',
    ];

    private const TABLE_COOPS = 'public.cooperativas';
    private const COOP_ID     = 'id_cooperativa';
    private const COOP_NOMBRE = 'nombre';

    /** @var string|null */
    private $resolvedTable = null;

    /**
     * @var array<string,array{name:string,type:string}>
     */
    private $columnInfo = [];

    /**
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $parts = $this->queryParts();

        $page    = max(1, $page);
        $perPage = max(5, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildFilters($filters, $parts);

        $countSql = "SELECT COUNT(*) AS total FROM {$parts['table']} s{$parts['joinsSql']}" . ($whereSql !== '' ? " $whereSql" : '');

        try {
            $countRow = $this->db->fetch($countSql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar el historial de seguimiento.', 0, $e);
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

        $sql = "SELECT {$parts['select']} FROM {$parts['table']} s{$parts['joinsSql']}"
            . ($whereSql !== '' ? " $whereSql" : '')
            . " ORDER BY {$parts['orderBy']} LIMIT :limit OFFSET :offset";

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el historial de seguimiento.', 0, $e);
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
     * @param array $filters
     * @return array<int,array<string,mixed>>
     */
    public function listarParaExportar(array $filters): array
    {
        $parts = $this->queryParts();
        [$whereSql, $params] = $this->buildFilters($filters, $parts);

        $sql = "SELECT {$parts['select']} FROM {$parts['table']} s{$parts['joinsSql']}"
            . ($whereSql !== '' ? " $whereSql" : '')
            . " ORDER BY {$parts['orderBy']}";

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al exportar el historial de seguimiento.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $table   = $this->resolveTable();
        $idCol   = $this->requireColumn('id');
        $coopCol = $this->requireColumn('coop');

        $columns = [$coopCol];
        $values  = [':coop'];
        $params  = [
            ':coop' => [$data['id_cooperativa'] ?? 0, PDO::PARAM_INT],
        ];

        $fechaCol = $this->column('fecha');
        if ($fechaCol !== null) {
            $columns[] = $fechaCol;
            $values[]  = ':fecha';
            $params[':fecha'] = [$data['fecha'] ?? date('Y-m-d'), PDO::PARAM_STR];
        }

        $tipoCol = $this->column('tipo');
        if ($tipoCol !== null) {
            $columns[] = $tipoCol;
            $values[]  = ':tipo';
            $params[':tipo'] = [
                (string)($data['tipo'] ?? ''),
                PDO::PARAM_STR,
            ];
        }

        $descCol = $this->requireColumn('descripcion');
        $columns[] = $descCol;
        $values[]  = ':descripcion';
        $params[':descripcion'] = [
            (string)($data['descripcion'] ?? ''),
            PDO::PARAM_STR,
        ];

        $ticketCol = $this->column('ticket');
        $contactNumberCol = $this->column('contact_number');
        $contactDataCol   = $this->column('contact_data');
        if ($ticketCol !== null) {
            $columns[] = $ticketCol;
            $values[]  = ':ticket';

            $ticketValue = $data['ticket'] ?? null;
            if ($ticketValue === '' || $ticketValue === null) {
                $params[':ticket'] = [null, PDO::PARAM_NULL];
            } elseif ($this->columnIsNumeric('ticket')) {
                $params[':ticket'] = [(int)$ticketValue, PDO::PARAM_INT];
            } else {
                $params[':ticket'] = [(string)$ticketValue, PDO::PARAM_STR];
            }
        }

        if ($contactNumberCol !== null) {
            $columns[] = $contactNumberCol;
            $values[]  = ':contact_number';

            $numberValue = $data['numero_contacto'] ?? null;
            if ($numberValue === '' || $numberValue === null) {
                $params[':contact_number'] = [null, PDO::PARAM_NULL];
            } else {
                $params[':contact_number'] = [(int)$numberValue, PDO::PARAM_INT];
            }
        }

        if ($contactDataCol !== null) {
            $columns[] = $contactDataCol;
            $values[]  = ':contact_data';

            $rawContact = $data['datos_contacto'] ?? null;
            if ($rawContact === null || $rawContact === '') {
                $params[':contact_data'] = [null, PDO::PARAM_NULL];
            } else {
                $json = $rawContact;
                if (is_array($rawContact)) {
                    $json = json_encode($rawContact, JSON_UNESCAPED_UNICODE);
                }
                if (!is_string($json) || $json === false) {
                    $json = json_encode(['valor' => (string)$rawContact], JSON_UNESCAPED_UNICODE);
                }
                $params[':contact_data'] = [$json, PDO::PARAM_STR];
            }
        }

        $usuarioCol = $this->column('usuario');
        if ($usuarioCol !== null) {
            $columns[] = $usuarioCol;
            $values[]  = ':usuario';

            $usuario = $data['creado_por'] ?? null;
            if ($usuario === null) {
                $params[':usuario'] = [null, PDO::PARAM_NULL];
            } else {
                $params[':usuario'] = [(int)$usuario, PDO::PARAM_INT];
            }
        }

        $sql = 'INSERT INTO ' . $table
            . ' (' . implode(', ', $columns) . ')
               VALUES (' . implode(', ', $values) . ')
               RETURNING ' . $idCol . ' AS id';

        try {
            $result = $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
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
        $sql = 'SELECT ' . self::COOP_ID . ' AS id, ' . self::COOP_NOMBRE . ' AS nombre'
            . ' FROM ' . self::TABLE_COOPS
            . ' ORDER BY ' . self::COOP_NOMBRE . ' ASC';

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudieron obtener las cooperativas.', 0, $e);
        }

        $list = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['nombre'])) {
                continue;
            }
            $list[] = [
                'id'     => (int)$row['id'],
                'nombre' => (string)$row['nombre'],
            ];
        }

        return $list;
    }

    /**
     * @return array<int,string>
     */
    public function catalogoTipos(): array
    {
        foreach (self::TIPOS_TABLE_CANDIDATES as $candidate) {
            [$schema, $table] = $this->splitTableName($candidate);
            if (!$this->tableExists($schema, $table)) {
                continue;
            }

            $column = $this->findColumnName($schema, $table, ['nombre', 'descripcion', 'titulo', 'etiqueta']);
            if ($column === null) {
                continue;
            }

            $sql = 'SELECT ' . $column . ' AS nombre FROM ' . $schema . '.' . $table . ' ORDER BY ' . $column . ' ASC';
            try {
                $rows = $this->db->fetchAll($sql);
            } catch (\Throwable $e) {
                continue;
            }

            $tipos = [];
            foreach ($rows as $row) {
                if (!isset($row['nombre'])) {
                    continue;
                }
                $value = trim((string)$row['nombre']);
                if ($value !== '') {
                    $tipos[] = $value;
                }
            }

            if ($tipos) {
                return $tipos;
            }
        }

        return ['Contacto', 'Soporte', 'Ticket', 'Reunión', 'Seguimiento'];
    }

    /**
     * @return array<string,mixed>
     */
    private function queryParts(): array
    {
        $table = $this->resolveTable();

        $idCol    = $this->requireColumn('id');
        $coopCol  = $this->requireColumn('coop');
        $descCol  = $this->requireColumn('descripcion');
        $fechaCol = $this->column('fecha');
        $tipoCol  = $this->column('tipo');
        $ticketCol = $this->column('ticket');
        $contactNumberCol = $this->column('contact_number');
        $contactDataCol   = $this->column('contact_data');
        $usuarioCol = $this->column('usuario');
        $createdCol = $this->column('created');

        $selectParts = [
            's.' . $idCol . ' AS id',
            's.' . $coopCol . ' AS id_cooperativa',
            'c.' . self::COOP_NOMBRE . ' AS cooperativa',
            ($fechaCol !== null
                ? 's.' . $fechaCol
                : ($createdCol !== null ? 'DATE(s.' . $createdCol . ')' : 'CURRENT_DATE')
            ) . ' AS fecha_registro',
            "COALESCE(s." . $descCol . ", '') AS descripcion",
        ];

        if ($tipoCol !== null) {
            $selectParts[] = "COALESCE(s." . $tipoCol . ", '') AS tipo";
        } else {
            $selectParts[] = "'' AS tipo";
        }

        if ($ticketCol !== null) {
            $selectParts[] = 's.' . $ticketCol . ' AS ticket';
        } else {
            $selectParts[] = 'NULL AS ticket';
        }

        if ($createdCol !== null) {
            $selectParts[] = 's.' . $createdCol . ' AS creado_en';
        } else {
            $selectParts[] = 'NULL AS creado_en';
        }

        if ($contactNumberCol !== null) {
            $selectParts[] = 's.' . $contactNumberCol . ' AS contact_number';
        } else {
            $selectParts[] = 'NULL AS contact_number';
        }

        if ($contactDataCol !== null) {
            $selectParts[] = 's.' . $contactDataCol . ' AS contact_data';
        } else {
            $selectParts[] = 'NULL AS contact_data';
        }

        $joins = [
            ' INNER JOIN ' . self::TABLE_COOPS . ' c ON c.' . self::COOP_ID . ' = s.' . $coopCol,
        ];

        if ($usuarioCol !== null) {
            $selectParts[] = 's.' . $usuarioCol . ' AS usuario_id';
            $selectParts[] = "COALESCE(u.nombre_completo, u.username, '') AS usuario_nombre";
            $joins[] = ' LEFT JOIN public.usuarios u ON u.id_usuario = s.' . $usuarioCol;
        } else {
            $selectParts[] = 'NULL AS usuario_id';
            $selectParts[] = "'' AS usuario_nombre";
        }

        $orderBy = $fechaCol !== null
            ? 's.' . $fechaCol . ' DESC'
            : ($createdCol !== null ? 's.' . $createdCol . ' DESC' : 's.' . $idCol . ' DESC');

        $joinsSql = '';
        foreach ($joins as $join) {
            $joinsSql .= $join;
        }

        return [
            'table'            => $table,
            'select'           => implode(",
                ", $selectParts),
            'joinsSql'         => $joinsSql,
            'orderBy'          => $orderBy,
            'fechaFilter'      => $fechaCol !== null ? 'DATE(s.' . $fechaCol . ')' : ($createdCol !== null ? 'DATE(s.' . $createdCol . ')' : null),
            'tipoFilter'       => $tipoCol !== null ? 's.' . $tipoCol : null,
            'ticketFilter'     => $ticketCol !== null ? 's.' . $ticketCol : null,
            'descripcionCol'   => 's.' . $descCol,
            'contactNumberCol' => $contactNumberCol !== null ? 's.' . $contactNumberCol : null,
            'contactDataCol'   => $contactDataCol !== null ? 's.' . $contactDataCol : null,
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $parts
     * @return array{0:string,1:array<string,array{0:mixed,1:int}>}
     */
    private function buildFilters(array $filters, array $parts): array
    {
        $conditions = [];
        $params = [];

        $fecha = isset($filters['fecha']) ? trim((string)$filters['fecha']) : '';
        if ($fecha !== '' && $parts['fechaFilter'] !== null) {
            $conditions[] = $parts['fechaFilter'] . ' = :fecha';
            $params[':fecha'] = [$fecha, PDO::PARAM_STR];
        } else {
            $desde = isset($filters['desde']) ? trim((string)$filters['desde']) : '';
            $hasta = isset($filters['hasta']) ? trim((string)$filters['hasta']) : '';
            if ($desde !== '' && $parts['fechaFilter'] !== null) {
                $conditions[] = $parts['fechaFilter'] . ' >= :desde';
                $params[':desde'] = [$desde, PDO::PARAM_STR];
            }
            if ($hasta !== '' && $parts['fechaFilter'] !== null) {
                $conditions[] = $parts['fechaFilter'] . ' <= :hasta';
                $params[':hasta'] = [$hasta, PDO::PARAM_STR];
            }
        }

        $coop = isset($filters['coop']) ? (int)$filters['coop'] : 0;
        if ($coop > 0) {
            $conditions[] = 's.' . $this->requireColumn('coop') . ' = :coop';
            $params[':coop'] = [$coop, PDO::PARAM_INT];
        }

        $tipo = isset($filters['tipo']) ? trim((string)$filters['tipo']) : '';
        if ($tipo !== '' && $parts['tipoFilter'] !== null) {
            $conditions[] = $parts['tipoFilter'] . ' = :tipo';
            $params[':tipo'] = [$tipo, PDO::PARAM_STR];
        }

        $ticket = isset($filters['ticket']) ? trim((string)$filters['ticket']) : '';
        if ($ticket !== '' && $parts['ticketFilter'] !== null) {
            $conditions[] = $parts['ticketFilter'] . '::text ILIKE :ticket';
            $params[':ticket'] = ['%' . $ticket . '%', PDO::PARAM_STR];
        }

        $texto = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($texto !== '') {
            $conditions[] = $parts['descripcionCol'] . ' ILIKE :texto';
            $params[':texto'] = ['%' . $texto . '%', PDO::PARAM_STR];
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$whereSql, $params];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapRow(array $row): array
    {
        $usuarioNombre = isset($row['usuario_nombre']) ? trim((string)$row['usuario_nombre']) : '';
        $usuarioId = isset($row['usuario_id']) ? (int)$row['usuario_id'] : 0;
        if ($usuarioNombre === '' && $usuarioId > 0) {
            $usuarioNombre = 'Usuario #' . $usuarioId;
        }

        $contactNumber = null;
        if (isset($row['contact_number'])) {
            $contactNumber = is_numeric($row['contact_number']) ? (int)$row['contact_number'] : null;
            if ($contactNumber !== null && $contactNumber <= 0) {
                $contactNumber = null;
            }
        }

        $contactData = null;
        if (isset($row['contact_data'])) {
            $raw = $row['contact_data'];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $contactData = $decoded;
                } else {
                    $contactData = $raw;
                }
            } elseif (is_array($raw)) {
                $contactData = $raw;
            }
        }

        return [
            'id'             => isset($row['id']) ? (int)$row['id'] : 0,
            'id_cooperativa' => isset($row['id_cooperativa']) ? (int)$row['id_cooperativa'] : 0,
            'cooperativa'    => isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
            'fecha'          => isset($row['fecha_registro']) ? (string)$row['fecha_registro'] : '',
            'tipo'           => isset($row['tipo']) ? (string)$row['tipo'] : '',
            'descripcion'    => isset($row['descripcion']) ? (string)$row['descripcion'] : '',
            'ticket'         => isset($row['ticket']) ? (string)$row['ticket'] : '',
            'usuario'        => $usuarioNombre,
            'usuario_id'     => $usuarioId,
            'creado_en'      => isset($row['creado_en']) ? (string)$row['creado_en'] : '',
            'contact_number' => $contactNumber,
            'contact_data'   => $contactData,
        ];
    }

    private function resolveTable(): string
    {
        if ($this->resolvedTable !== null) {
            return $this->resolvedTable;
        }

        foreach (self::TABLE_CANDIDATES as $candidate) {
            [$schema, $table] = $this->splitTableName($candidate);
            if ($this->tableExists($schema, $table)) {
                $this->resolvedTable = $schema . '.' . $table;
                return $this->resolvedTable;
            }
        }

        $this->resolvedTable = self::TABLE_CANDIDATES[0];
        return $this->resolvedTable;
    }

    /**
     * @return array<string,array{name:string,type:string}>
     */
    private function tableColumns(): array
    {
        if ($this->columnInfo) {
            return $this->columnInfo;
        }

        [$schema, $table] = $this->splitTableName($this->resolveTable());

        $sql = 'SELECT column_name, data_type FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table';

        try {
            $rows = $this->db->fetchAll($sql, [
                ':schema' => [$schema, PDO::PARAM_STR],
                ':table'  => [$table, PDO::PARAM_STR],
            ]);
        } catch (\Throwable $e) {
            $this->columnInfo = [];
            return $this->columnInfo;
        }

        $info = [];
        foreach ($rows as $row) {
            if (!isset($row['column_name'])) {
                continue;
            }
            $name = (string)$row['column_name'];
            $type = isset($row['data_type']) ? (string)$row['data_type'] : 'text';
            $info[strtolower($name)] = [
                'name' => $name,
                'type' => $type,
            ];
        }

        $this->columnInfo = $info;
        return $this->columnInfo;
    }

    private function column(string $logical): ?string
    {
        $map = [
            'id'          => ['id', 'id_seguimiento', 'seguimiento_id'],
            'coop'        => ['id_cooperativa', 'cooperativa_id', 'id_entidad'],
            'fecha'       => ['fecha', 'fecha_actividad', 'fecha_seguimiento', 'dia', 'fecha_registro'],
            'tipo'        => ['tipo', 'tipo_actividad', 'tipo_evento', 'categoria'],
            'descripcion' => ['descripcion', 'detalle', 'comentario', 'observacion', 'nota'],
            'ticket'      => ['ticket_id', 'id_ticket', 'ticket', 'ticket_numero'],
            'usuario'     => ['creado_por', 'usuario_id', 'registrado_por', 'created_by'],
            'created'     => ['created_at', 'creado_en', 'fecha_creacion', 'registrado_el'],
            'contact_number' => ['numero_contacto', 'contact_number', 'num_contacto', 'contacto_numero'],
            'contact_data'   => ['datos_contacto', 'contact_data', 'contacto_datos', 'contacto_json'],
        ];

        $candidates = $map[$logical] ?? [];
        $columns = $this->tableColumns();

        foreach ($candidates as $candidate) {
            $lower = strtolower($candidate);
            if (isset($columns[$lower])) {
                return $columns[$lower]['name'];
            }
        }

        return null;
    }

    private function requireColumn(string $logical): string
    {
        $column = $this->column($logical);
        if ($column === null) {
            throw new RuntimeException('No existe la columna requerida "' . $logical . '" en la tabla de seguimiento.');
        }
        return $column;
    }

    private function columnIsNumeric(string $logical): bool
    {
        $column = $this->column($logical);
        if ($column === null) {
            return false;
        }

        $columns = $this->tableColumns();
        $info = $columns[strtolower($column)] ?? null;
        if (!$info) {
            return false;
        }

        $type = strtolower($info['type']);
        return in_array($type, ['integer', 'bigint', 'smallint', 'numeric', 'decimal'], true);
    }

    private function tableExists(string $schema, string $table): bool
    {
        $sql = 'SELECT 1 FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table LIMIT 1';

        try {
            $row = $this->db->fetch($sql, [
                ':schema' => [$schema, PDO::PARAM_STR],
                ':table'  => [$table, PDO::PARAM_STR],
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        return $row !== null;
    }

    private function findColumnName(string $schema, string $table, array $candidates): ?string
    {
        $sql = 'SELECT column_name FROM information_schema.columns WHERE table_schema = :schema AND table_name = :table';

        try {
            $rows = $this->db->fetchAll($sql, [
                ':schema' => [$schema, PDO::PARAM_STR],
                ':table'  => [$table, PDO::PARAM_STR],
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        $available = [];
        foreach ($rows as $row) {
            if (!isset($row['column_name'])) {
                continue;
            }
            $available[strtolower((string)$row['column_name'])] = (string)$row['column_name'];
        }

        foreach ($candidates as $candidate) {
            $key = strtolower($candidate);
            if (isset($available[$key])) {
                return $available[$key];
            }
        }

        return null;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitTableName(string $table): array
    {
        $parts = explode('.', $table, 2);
        if (count($parts) === 2) {
            return [$parts[0], $parts[1]];
        }
        return ['public', $parts[0]];
    }
}
