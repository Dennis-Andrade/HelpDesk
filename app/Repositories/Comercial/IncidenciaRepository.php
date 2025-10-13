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
    private const COL_DEPTO    = 'departamento_id';
    private const COL_ASUNTO   = 'asunto';
    private const COL_DESCRIP  = 'descripcion';
    private const COL_PRIOR    = 'prioridad';
    private const COL_ESTADO   = 'estado';
    private const COL_TIPO_NAME = 'tipo_incidencia';
    private const COL_TIPO_ID   = 'tipo_incidencia_id';
    private const COL_TIPO_DEP  = 'tipo_incidencia_departamento_id';
    private const COL_TICKET   = 'id_ticket';
    private const COL_CREATED  = 'created_at';
    private const COL_UPDATED  = 'updated_at';
    private const COL_USER     = 'creado_por';

    private const T_COOP       = 'public.cooperativas';
    private const COOP_ID      = 'id_cooperativa';
    private const COOP_NOMBRE  = 'nombre';

    private const T_CONTACTOS  = 'public.agenda_contactos';
    private const CONTACT_COOP = 'id_cooperativa';

    private const T_DEPARTAMENTOS = 'public.departamentos';
    private const DEP_ID          = 'id';
    private const DEP_CLAVE       = 'clave';
    private const DEP_NOMBRE      = 'nombre';

    private const T_TIPOS_DEP = 'public.tipos_incidencias_departamento';
    private const TIPO_ID     = 'id';
    private const TIPO_DEPTO  = 'departamento_id';
    private const TIPO_NOMBRE = 'nombre';
    private const TIPO_ORDEN  = 'orden';
    private const TIPO_REF    = 'referencia_id';

    private const T_TIPOS_GLOBAL      = 'public.incidencia_tipos';
    private const TIPO_GLOBAL_ID      = 'id';
    private const TIPO_GLOBAL_NOMBRE  = 'nombre';

    /** @var array<string,bool>|null */
    private $incidenciaColumns = null;

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

        $estado        = isset($filters['estado']) ? trim((string)$filters['estado']) : '';
        $coopId        = isset($filters['coop']) ? (int)$filters['coop'] : 0;
        $departamento  = isset($filters['departamento']) ? (int)$filters['departamento'] : 0;
        $ticket        = isset($filters['ticket']) ? trim((string)$filters['ticket']) : '';
        $ticketId      = (int)preg_replace('/\D+/', '', $ticket);

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

        if ($departamento > 0 && $this->incidenciaHasColumn(self::COL_DEPTO)) {
            $where[] = 'i.' . self::COL_DEPTO . ' = :departamento_id';
            $bindings[':departamento_id'] = [$departamento, PDO::PARAM_INT];
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

        $tipoFragments = $this->tipoSelectFragments();
        $joinTipos = '';
        if (!empty($tipoFragments['joins'])) {
            $joinTipos = "\n            " . implode("\n            ", $tipoFragments['joins']);
        }

        $sql = '
            SELECT
                i.' . self::COL_ID . ' AS id,
                i.' . self::COL_COOP . ' AS id_cooperativa,
                i.' . self::COL_DEPTO . ' AS departamento_id,
                dep.' . self::DEP_NOMBRE . ' AS departamento_nombre,
                c.' . self::COOP_NOMBRE . ' AS cooperativa,
                i.' . self::COL_ASUNTO . ' AS asunto,
                ' . $tipoFragments['select_nombre'] . ',
                ' . $tipoFragments['select_id'] . ',
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
            LEFT JOIN ' . self::T_DEPARTAMENTOS . ' dep
                ON dep.' . self::DEP_ID . ' = i.' . self::COL_DEPTO . '
            ' . $joinTipos . '
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

    private function incidenciaHasColumn(string $column): bool
    {
        if ($this->incidenciaColumns === null) {
            $parts  = explode('.', self::T_INCIDENCIA, 2);
            $schema = $parts[0] ?? 'public';
            $table  = $parts[1] ?? $parts[0];

            $sql = '
                SELECT column_name
                FROM information_schema.columns
                WHERE table_schema = :schema
                  AND table_name = :table
            ';

            try {
                $rows = $this->db->fetchAll($sql, [
                    ':schema' => [$schema, PDO::PARAM_STR],
                    ':table'  => [$table, PDO::PARAM_STR],
                ]);
            } catch (\Throwable $e) {
                $this->incidenciaColumns = [];
                return false;
            }

            $map = [];
            foreach ($rows as $row) {
                if (!isset($row['column_name'])) {
                    continue;
                }
                $map[strtolower((string)$row['column_name'])] = true;
            }

            $this->incidenciaColumns = $map;
        }

        return isset($this->incidenciaColumns[strtolower($column)]);
    }

    /**
     * Determina las expresiones y joins necesarios para obtener el tipo de incidencia.
     *
     * @return array{select_nombre:string,select_id:string,joins:array<int,string>}
     */
    private function tipoSelectFragments(): array
    {
        $hasNombre = $this->incidenciaHasColumn(self::COL_TIPO_NAME);
        $hasTipoDepto = $this->incidenciaHasColumn(self::COL_TIPO_DEP);
        $hasTipoId = $this->incidenciaHasColumn(self::COL_TIPO_ID);

        $selectNombre = "'' AS tipo_incidencia";
        $selectId = 'NULL AS tipo_departamento_id';
        $joins = [];

        if ($hasTipoDepto) {
            $selectId = 'i.' . self::COL_TIPO_DEP . ' AS tipo_departamento_id';
            $joins[] = 'LEFT JOIN ' . self::T_TIPOS_DEP . ' tipo_dep ON tipo_dep.' . self::TIPO_ID . ' = i.' . self::COL_TIPO_DEP;
            $joins[] = 'LEFT JOIN ' . self::T_TIPOS_GLOBAL . ' tipo_global ON tipo_global.' . self::TIPO_GLOBAL_ID . ' = tipo_dep.' . self::TIPO_REF;

            $nombreExprParts = [];
            $nombreExprParts[] = 'tipo_dep.' . self::TIPO_NOMBRE;
            $nombreExprParts[] = 'tipo_global.' . self::TIPO_GLOBAL_NOMBRE;
            if ($hasNombre) {
                $nombreExprParts[] = 'i.' . self::COL_TIPO_NAME;
            }
            $selectNombre = 'COALESCE(' . implode(', ', $nombreExprParts) . ') AS tipo_incidencia';
        } elseif ($hasTipoId) {
            $selectId = 'i.' . self::COL_TIPO_ID . ' AS tipo_departamento_id';
            $joins[] = 'LEFT JOIN ' . self::T_TIPOS_DEP . ' tipo_dep ON tipo_dep.' . self::TIPO_ID . ' = i.' . self::COL_TIPO_ID;
            $joins[] = 'LEFT JOIN ' . self::T_TIPOS_GLOBAL . ' tipo_global ON tipo_global.' . self::TIPO_GLOBAL_ID . ' = i.' . self::COL_TIPO_ID;

            $nombreExprParts = [];
            $nombreExprParts[] = 'tipo_dep.' . self::TIPO_NOMBRE;
            $nombreExprParts[] = 'tipo_global.' . self::TIPO_GLOBAL_NOMBRE;
            if ($hasNombre) {
                $nombreExprParts[] = 'i.' . self::COL_TIPO_NAME;
            }
            $selectNombre = 'COALESCE(' . implode(', ', $nombreExprParts) . ') AS tipo_incidencia';
        } elseif ($hasNombre) {
            $selectNombre = 'i.' . self::COL_TIPO_NAME . ' AS tipo_incidencia';
        }

        return [
            'select_nombre' => $selectNombre,
            'select_id'     => $selectId,
            'joins'         => $joins,
        ];
    }

    /**
     * Crea una nueva incidencia y devuelve su identificador.
     *
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $descripcion = isset($data['descripcion']) ? trim((string)$data['descripcion']) : '';
        $descripcionParam = $descripcion === ''
            ? [null, PDO::PARAM_NULL]
            : [$descripcion, PDO::PARAM_STR];

        $departamentoId = isset($data['departamento_id']) ? (int)$data['departamento_id'] : 0;
        $departamentoParam = $departamentoId > 0
            ? [$departamentoId, PDO::PARAM_INT]
            : [null, PDO::PARAM_NULL];

        $tipoDepartamentoId = isset($data['tipo_incidencia_id']) ? (int)$data['tipo_incidencia_id'] : 0;
        $tipoDepartamentoParam = $tipoDepartamentoId > 0
            ? [$tipoDepartamentoId, PDO::PARAM_INT]
            : [null, PDO::PARAM_NULL];

        $columns = [self::COL_COOP];
        $values  = [':coop'];
        $params  = [
            ':coop' => [$data['id_cooperativa'] ?? 0, PDO::PARAM_INT],
        ];

        if ($this->incidenciaHasColumn(self::COL_DEPTO)) {
            $columns[] = self::COL_DEPTO;
            $values[]  = ':departamento';
            $params[':departamento'] = $departamentoParam;
        }

        $columns[] = self::COL_ASUNTO;
        $values[]  = ':asunto';
        $params[':asunto'] = [$data['asunto'] ?? '', PDO::PARAM_STR];

        $hasTipoNombre = $this->incidenciaHasColumn(self::COL_TIPO_NAME);
        $hasTipoDepto  = $this->incidenciaHasColumn(self::COL_TIPO_DEP);
        $hasTipoId     = $this->incidenciaHasColumn(self::COL_TIPO_ID);

        if ($hasTipoDepto) {
            $columns[] = self::COL_TIPO_DEP;
            $values[]  = ':tipo_departamento';
            $params[':tipo_departamento'] = $tipoDepartamentoParam;
        } elseif ($hasTipoId) {
            $columns[] = self::COL_TIPO_ID;
            $values[]  = ':tipo_departamento';
            $params[':tipo_departamento'] = $tipoDepartamentoParam;
        }

        if ($hasTipoNombre) {
            $columns[] = self::COL_TIPO_NAME;
            $values[]  = ':tipo_nombre';
            $params[':tipo_nombre'] = [$data['tipo_incidencia'] ?? '', PDO::PARAM_STR];
        }

        $columns[] = self::COL_DESCRIP;
        $values[]  = ':descripcion';
        $params[':descripcion'] = $descripcionParam;

        $columns[] = self::COL_PRIOR;
        $values[]  = ':prioridad';
        $params[':prioridad'] = [$data['prioridad'] ?? '', PDO::PARAM_STR];

        $columns[] = self::COL_ESTADO;
        $values[]  = ':estado';
        $params[':estado'] = [$data['estado'] ?? 'Enviado', PDO::PARAM_STR];

        $columns[] = self::COL_USER;
        $values[]  = ':usuario';
        $params[':usuario'] = [$data['creado_por'] ?? null, isset($data['creado_por']) ? PDO::PARAM_INT : PDO::PARAM_NULL];

        $sql = '
            INSERT INTO ' . self::T_INCIDENCIA . ' (
                ' . implode(', ', $columns) . '
            ) VALUES (
                ' . implode(', ', $values) . '
            )
            RETURNING ' . self::COL_ID . '
        ';

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
        $descripcion = isset($data['descripcion']) ? trim((string)$data['descripcion']) : '';
        $descripcionParam = $descripcion === ''
            ? [null, PDO::PARAM_NULL]
            : [$descripcion, PDO::PARAM_STR];

        $departamentoId = isset($data['departamento_id']) ? (int)$data['departamento_id'] : 0;
        $departamentoParam = $departamentoId > 0
            ? [$departamentoId, PDO::PARAM_INT]
            : [null, PDO::PARAM_NULL];

        $tipoDepartamentoId = isset($data['tipo_incidencia_id']) ? (int)$data['tipo_incidencia_id'] : 0;
        $tipoDepartamentoParam = $tipoDepartamentoId > 0
            ? [$tipoDepartamentoId, PDO::PARAM_INT]
            : [null, PDO::PARAM_NULL];

        $sets = [
            self::COL_ASUNTO . ' = :asunto',
            self::COL_PRIOR . ' = :prioridad',
            self::COL_ESTADO . ' = :estado',
            self::COL_DESCRIP . ' = :descripcion',
            self::COL_UPDATED . ' = NOW()'
        ];

        $params = [
            ':id'          => [$id, PDO::PARAM_INT],
            ':asunto'      => [$data['asunto'] ?? '', PDO::PARAM_STR],
            ':prioridad'   => [$data['prioridad'] ?? '', PDO::PARAM_STR],
            ':estado'      => [$data['estado'] ?? 'Enviado', PDO::PARAM_STR],
            ':descripcion' => $descripcionParam,
        ];

        $hasTipoNombre = $this->incidenciaHasColumn(self::COL_TIPO_NAME);
        $hasTipoDepto  = $this->incidenciaHasColumn(self::COL_TIPO_DEP);
        $hasTipoId     = $this->incidenciaHasColumn(self::COL_TIPO_ID);

        if ($hasTipoNombre) {
            $sets[] = self::COL_TIPO_NAME . ' = :tipo_nombre';
            $params[':tipo_nombre'] = [$data['tipo_incidencia'] ?? '', PDO::PARAM_STR];
        }

        if ($this->incidenciaHasColumn(self::COL_DEPTO)) {
            $sets[] = self::COL_DEPTO . ' = :departamento';
            $params[':departamento'] = $departamentoParam;
        }

        if ($hasTipoDepto) {
            $sets[] = self::COL_TIPO_DEP . ' = :tipo_departamento';
            $params[':tipo_departamento'] = $tipoDepartamentoParam;
        } elseif ($hasTipoId) {
            $sets[] = self::COL_TIPO_ID . ' = :tipo_departamento';
            $params[':tipo_departamento'] = $tipoDepartamentoParam;
        }

        $sql = '
            UPDATE ' . self::T_INCIDENCIA . '
            SET ' . implode(",\n                ", $sets) . '
            WHERE ' . self::COL_ID . ' = :id
        ';

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

        $tipoFragments = $this->tipoSelectFragments();
        $joinTipos = '';
        if (!empty($tipoFragments['joins'])) {
            $joinTipos = "\n            " . implode("\n            ", $tipoFragments['joins']);
        }

        $sql = '
            SELECT
                i.' . self::COL_ID . ' AS id,
                i.' . self::COL_COOP . ' AS id_cooperativa,
                i.' . self::COL_DEPTO . ' AS departamento_id,
                dep.' . self::DEP_NOMBRE . ' AS departamento_nombre,
                c.' . self::COOP_NOMBRE . ' AS cooperativa,
                i.' . self::COL_ASUNTO . ' AS asunto,
                ' . $tipoFragments['select_nombre'] . ',
                ' . $tipoFragments['select_id'] . ',
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
            LEFT JOIN ' . self::T_DEPARTAMENTOS . ' dep
                ON dep.' . self::DEP_ID . ' = i.' . self::COL_DEPTO . '
            ' . $joinTipos . '
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
     * Catálogo de departamentos disponibles.
     *
     * @return array<int,array{id:int,clave:string,nombre:string}>
     */
    public function catalogoDepartamentos(): array
    {
        $sql = '
            SELECT ' . self::DEP_ID . ' AS id, ' . self::DEP_CLAVE . ' AS clave, ' . self::DEP_NOMBRE . ' AS nombre
            FROM ' . self::T_DEPARTAMENTOS . '
            ORDER BY ' . self::DEP_NOMBRE . '
        ';

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el catálogo de departamentos.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['nombre'])) {
                continue;
            }
            $items[] = [
                'id'     => (int)$row['id'],
                'clave'  => isset($row['clave']) ? (string)$row['clave'] : '',
                'nombre' => (string)$row['nombre'],
            ];
        }

        return $items;
    }

    /**
     * Catálogo de tipos por departamento.
     *
     * @return array<int,array<int,array{id:int,departamento_id:int,nombre:string}>>
     */
    public function catalogoTiposPorDepartamento(): array
    {
        $sql = '
            SELECT
                ' . self::TIPO_ID . ' AS id,
                ' . self::TIPO_DEPTO . ' AS departamento_id,
                ' . self::TIPO_NOMBRE . ' AS nombre
            FROM ' . self::T_TIPOS_DEP . '
            ORDER BY ' . self::TIPO_DEPTO . ', ' . self::TIPO_ORDEN . ', ' . self::TIPO_NOMBRE . '
        ';

        try {
            $rows = $this->db->fetchAll($sql);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el catálogo de tipos de incidencias.', 0, $e);
        }

        $map = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['departamento_id'], $row['nombre'])) {
                continue;
            }
            $deptId = (int)$row['departamento_id'];
            if (!isset($map[$deptId])) {
                $map[$deptId] = [];
            }
            $map[$deptId][] = [
                'id'              => (int)$row['id'],
                'departamento_id' => $deptId,
                'nombre'          => (string)$row['nombre'],
            ];
        }

        return $map;
    }

    /**
     * Obtiene un tipo de incidencia específico.
     */
    public function findTipoPorId(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $sql = '
            SELECT
                ' . self::TIPO_ID . ' AS id,
                ' . self::TIPO_DEPTO . ' AS departamento_id,
                ' . self::TIPO_NOMBRE . ' AS nombre
            FROM ' . self::T_TIPOS_DEP . '
            WHERE ' . self::TIPO_ID . ' = :id
        ';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el tipo de incidencia solicitado.', 0, $e);
        }

        if (!$row || !isset($row['id'], $row['departamento_id'], $row['nombre'])) {
            return null;
        }

        return [
            'id'              => (int)$row['id'],
            'departamento_id' => (int)$row['departamento_id'],
            'nombre'          => (string)$row['nombre'],
        ];
    }

    /**
     * Devuelve el primer tipo disponible para un departamento.
     */
    public function findPrimerTipoPorDepartamento(int $departamentoId): ?array
    {
        if ($departamentoId < 1) {
            return null;
        }

        $sql = '
            SELECT
                ' . self::TIPO_ID . ' AS id,
                ' . self::TIPO_DEPTO . ' AS departamento_id,
                ' . self::TIPO_NOMBRE . ' AS nombre
            FROM ' . self::T_TIPOS_DEP . '
            WHERE ' . self::TIPO_DEPTO . ' = :departamento
            ORDER BY ' . self::TIPO_ORDEN . ', ' . self::TIPO_NOMBRE . '
            LIMIT 1
        ';

        try {
            $row = $this->db->fetch($sql, [':departamento' => [$departamentoId, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el tipo de incidencia por departamento.', 0, $e);
        }

        if (!$row || !isset($row['id'], $row['departamento_id'], $row['nombre'])) {
            return null;
        }

        return [
            'id'              => (int)$row['id'],
            'departamento_id' => (int)$row['departamento_id'],
            'nombre'          => (string)$row['nombre'],
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
