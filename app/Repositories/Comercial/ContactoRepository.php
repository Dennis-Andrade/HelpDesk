<?php
namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

/**
 * Repositorio para gestionar los contactos de entidades.
 *
 * Adaptado para mapear contra public.agenda_contactos (dump del proyecto).
 */
final class ContactoRepository extends BaseRepository
{
    /** @var string Nombre de la tabla de contactos. */
    private const T_CONTACTO      = 'public.agenda_contactos';
    /** @var string Clave primaria del contacto (en agenda_contactos). */
    private const COL_ID          = 'id_evento';
    /** @var string Columna de relación con la cooperativa. */
    private const COL_COOP        = 'id_cooperativa';
    /** @var string Columna "principal" nombre del contacto (usar COALESCE para fallback). */
    private const COL_NOMBRE_RAW  = 'oficial_nombre'; // preferible; si no está, usar 'contacto'
    private const COL_CONTACTO_ALT= 'contacto';
    private const COL_TITULO      = 'titulo';
    private const COL_CARGO       = 'cargo';
    private const COL_TEL         = 'telefono_contacto';
    private const COL_MAIL        = 'oficial_correo';
    private const COL_NOTA        = 'nota';
    private const COL_FECHA_EVENTO= 'fecha_evento';

    /** @var string Tabla de cooperativas. */
    private const T_COOP          = 'public.cooperativas';
    private const COL_COOP_ID     = 'id_cooperativa';
    private const COL_COOP_NOMBRE = 'nombre';
    /** @var string Tabla legacy de contactos utilizada por seguimientos. */
    private const T_CONTACTOS_COOP = 'public.contactos_cooperativa';

    /**
     * Búsqueda paginada de contactos.
     *
     * @param string $q
     * @param int    $page
     * @param int    $perPage
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function search(string $q, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $q    = trim($q);
        $hasQ = $q !== '' ? 1 : 0;
        $like = '%' . $q . '%';

        // Nombre real = COALESCE(oficial_nombre, contacto)
        $nombreExpr = "COALESCE(c." . self::COL_NOMBRE_RAW . ", c." . self::COL_CONTACTO_ALT . ")";

        // Calcular total
        $countSql = '
            SELECT COUNT(*) AS total
            FROM ' . self::T_CONTACTO . ' c
            INNER JOIN ' . self::T_COOP . ' e
                ON e.' . self::COL_COOP_ID . ' = c.' . self::COL_COOP . '
            WHERE (
                :has_q = 0
                OR unaccent(lower(' . $nombreExpr . ')) LIKE unaccent(lower(:like))
                OR unaccent(lower(e.' . self::COL_COOP_NOMBRE . ')) LIKE unaccent(lower(:like))
            )
        ';
        $bindings = [
            ':has_q' => [$hasQ, PDO::PARAM_INT],
            ':like'  => [$like, PDO::PARAM_STR],
        ];
        try {
            $row = $this->db->fetch($countSql, $bindings);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar contactos.', 0, $e);
        }
        $total = $row ? (int)$row['total'] : 0;
        if ($total === 0) {
            return [
                'items'   => [],
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        // Obtener listado
        $sql = '
            SELECT
                c.' . self::COL_ID . ' AS id,
                c.' . self::COL_COOP . ' AS id_entidad,
                e.' . self::COL_COOP_NOMBRE . ' AS entidad_nombre,
                ' . $nombreExpr . ' AS nombre,
                c.' . self::COL_TITULO . ' AS titulo,
                c.' . self::COL_CARGO . ' AS cargo,
                c.' . self::COL_TEL . ' AS telefono,
                c.' . self::COL_MAIL . ' AS correo,
                c.' . self::COL_NOTA . ' AS nota,
                c.' . self::COL_FECHA_EVENTO . ' AS fecha_evento
            FROM ' . self::T_CONTACTO . ' c
            INNER JOIN ' . self::T_COOP . ' e
                ON e.' . self::COL_COOP_ID . ' = c.' . self::COL_COOP . '
            WHERE (
                :has_q = 0
                OR unaccent(lower(' . $nombreExpr . ')) LIKE unaccent(lower(:like))
                OR unaccent(lower(e.' . self::COL_COOP_NOMBRE . ')) LIKE unaccent(lower(:like))
            )
            ORDER BY ' . $nombreExpr . '
            LIMIT :limit OFFSET :offset
        ';
        $params = $bindings;
        $params[':limit']  = [$perPage, PDO::PARAM_INT];
        $params[':offset'] = [$offset, PDO::PARAM_INT];

        try {
            $items = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al buscar contactos.', 0, $e);
        }
        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * Devuelve sugerencias rápidas para el cuadro de búsqueda.
     *
     * @param string $q     Texto a buscar.
     * @param int    $limit Límite máximo de registros.
     * @return array<int,array<string,mixed>>
     */
    public function suggest(string $q, int $limit = 8): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $like  = '%' . $q . '%';
        $nombreExpr = "COALESCE(c." . self::COL_NOMBRE_RAW . ", c." . self::COL_CONTACTO_ALT . ")";

        $sql = '
            SELECT
                c.' . self::COL_ID . ' AS id,
                c.' . self::COL_COOP . ' AS id_entidad,
                e.' . self::COL_COOP_NOMBRE . ' AS entidad_nombre,
                ' . $nombreExpr . ' AS nombre,
                c.' . self::COL_CARGO . ' AS cargo
            FROM ' . self::T_CONTACTO . ' c
            INNER JOIN ' . self::T_COOP . ' e
                ON e.' . self::COL_COOP_ID . ' = c.' . self::COL_COOP . '
            WHERE (
                unaccent(lower(' . $nombreExpr . ')) LIKE unaccent(lower(:like))
                OR unaccent(lower(e.' . self::COL_COOP_NOMBRE . ')) LIKE unaccent(lower(:like))
            )
            ORDER BY ' . $nombreExpr . '
            LIMIT :limit
        ';

        $params = [
            ':like'  => [$like, PDO::PARAM_STR],
            ':limit' => [$limit, PDO::PARAM_INT],
        ];

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener sugerencias de contactos.', 0, $e);
        }

        $suggestions = [];
        foreach ($rows as $row) {
            $suggestions[] = [
                'id'             => isset($row['id']) ? (int)$row['id'] : null,
                'id_entidad'     => isset($row['id_entidad']) ? (int)$row['id_entidad'] : null,
                'entidad_nombre' => (string)($row['entidad_nombre'] ?? ''),
                'nombre'         => (string)($row['nombre'] ?? ''),
                'cargo'          => (string)($row['cargo'] ?? ''),
            ];
        }

        return $suggestions;
    }

    /**
     * Obtiene el listado completo de contactos para exportación.
     *
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listForExport(array $filters = []): array
    {
        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        $entidadId = isset($filters['entidad']) ? (int)$filters['entidad'] : 0;

        $nombreExpr = "COALESCE(c." . self::COL_NOMBRE_RAW . ", c." . self::COL_CONTACTO_ALT . ")";
        $conditions = [];
        $params = [];

        if ($q !== '') {
            $conditions[] = '(
                unaccent(lower(' . $nombreExpr . ')) LIKE unaccent(lower(:like))
                OR unaccent(lower(e.' . self::COL_COOP_NOMBRE . ')) LIKE unaccent(lower(:like))
            )';
            $params[':like'] = ['%' . $q . '%', PDO::PARAM_STR];
        }

        if ($entidadId > 0) {
            $conditions[] = 'c.' . self::COL_COOP . ' = :entidad';
            $params[':entidad'] = [$entidadId, PDO::PARAM_INT];
        }

        $whereSql = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';

        $sql = '
            SELECT
                c.' . self::COL_ID . ' AS id,
                c.' . self::COL_COOP . ' AS id_entidad,
                e.' . self::COL_COOP_NOMBRE . ' AS entidad_nombre,
                ' . $nombreExpr . ' AS nombre,
                c.' . self::COL_TITULO . ' AS titulo,
                c.' . self::COL_CARGO . ' AS cargo,
                c.' . self::COL_TEL . ' AS telefono,
                c.' . self::COL_MAIL . ' AS correo,
                c.' . self::COL_NOTA . ' AS nota,
                c.' . self::COL_FECHA_EVENTO . ' AS fecha_evento
            FROM ' . self::T_CONTACTO . ' c
            INNER JOIN ' . self::T_COOP . ' e
                ON e.' . self::COL_COOP_ID . ' = c.' . self::COL_COOP . '
            ' . $whereSql . '
            ORDER BY ' . $nombreExpr . '
        ';

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al preparar los contactos para exportación.', 0, $e);
        }
    }

    /**
     * Obtiene un contacto por su identificador.
     *
     * @param int $id
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        $nombreExpr = "COALESCE(c." . self::COL_NOMBRE_RAW . ", c." . self::COL_CONTACTO_ALT . ")";

        $sql = '
            SELECT
                c.' . self::COL_ID . ' AS id,
                c.' . self::COL_COOP . ' AS id_entidad,
                e.' . self::COL_COOP_NOMBRE . ' AS entidad_nombre,
                ' . $nombreExpr . ' AS nombre,
                c.' . self::COL_TITULO . ' AS titulo,
                c.' . self::COL_CARGO . ' AS cargo,
                c.' . self::COL_TEL . ' AS telefono,
                c.' . self::COL_MAIL . ' AS correo,
                c.' . self::COL_NOTA . ' AS nota,
                c.' . self::COL_FECHA_EVENTO . ' AS fecha_evento,
                c.' . self::COL_CONTACTO_ALT . ' AS contacto_ref
            FROM ' . self::T_CONTACTO . ' c
            INNER JOIN ' . self::T_COOP . ' e
                ON e.' . self::COL_COOP_ID . ' = c.' . self::COL_COOP . '
            WHERE c.' . self::COL_ID . ' = :id
            LIMIT 1
        ';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el contacto.', 0, $e);
        }

        if (!$row) {
            return null;
        }

        return [
            'id'             => isset($row['id']) ? (int)$row['id'] : $id,
            'id_entidad'     => isset($row['id_entidad']) ? (int)$row['id_entidad'] : null,
            'entidad_nombre' => (string)($row['entidad_nombre'] ?? ''),
            'nombre'         => (string)($row['nombre'] ?? ''),
            'titulo'         => (string)($row['titulo'] ?? ''),
            'cargo'          => (string)($row['cargo'] ?? ''),
            'telefono'       => (string)($row['telefono'] ?? ''),
            'correo'         => (string)($row['correo'] ?? ''),
            'nota'           => (string)($row['nota'] ?? ''),
            'fecha_evento'   => (string)($row['fecha_evento'] ?? ''),
            'contacto_cooperativa_id' => $this->extractContactoCoopId($row['contacto_ref'] ?? null),
        ];
    }

    /**
     * Inserta un nuevo contacto y devuelve su ID.
     *
     * @param array<string,mixed> $d
     * @return int
     */
    public function create(array $d): int
    {
        // Insertar en agenda_contactos: mapear a las columnas reales
        $sql = '
            INSERT INTO ' . self::T_CONTACTO . ' (
                ' . self::COL_COOP . ',
                ' . self::COL_NOMBRE_RAW . ',
                ' . self::COL_TITULO . ',
                ' . self::COL_CARGO . ',
                ' . self::COL_TEL . ',
                ' . self::COL_MAIL . ',
                ' . self::COL_NOTA . ',
                ' . self::COL_FECHA_EVENTO . '
            ) VALUES (
                :id_cooperativa,
                :nombre,
                :titulo,
                :cargo,
                :telefono,
                :correo,
                :nota,
                :fecha_evento
            ) RETURNING ' . self::COL_ID . ' AS id
        ';
        $params = [
            ':id_cooperativa' => [$d['id_cooperativa'] ?? null, PDO::PARAM_INT],
            ':nombre'         => $this->nullableStringParam($d['nombre'] ?? ($d['oficial_nombre'] ?? '')),
            ':titulo'         => $this->nullableStringParam($d['titulo'] ?? ''),
            ':cargo'          => $this->nullableStringParam($d['cargo'] ?? ''),
            ':telefono'       => $this->nullableStringParam($d['telefono_contacto'] ?? ($d['telefono'] ?? '')),
            ':correo'         => $this->nullableStringParam($d['email_contacto'] ?? ($d['oficial_correo'] ?? '')),
            ':nota'           => $this->nullableStringParam($d['nota'] ?? ''),
            ':fecha_evento'   => $this->dateParam($d['fecha_evento'] ?? null),
        ];
        try {
            $rows = $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al crear el contacto.', 0, $e);
        }
        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : null;
        if (!$row || !isset($row['id'])) {
            throw new RuntimeException('INSERT contactos no devolvió id');
        }
        $agendaId = (int)$row['id'];

        $contactoId = $this->syncContactoCooperativa($d, null);
        if ($contactoId !== null) {
            $this->linkAgendaContacto($agendaId, $contactoId);
        }

        return $agendaId;
    }

    /**
     * Actualiza un contacto existente.
     *
     * @param int                 $id
     * @param array<string,mixed> $d
     */
    public function update(int $id, array $d): void
    {
        $sql = '
            UPDATE ' . self::T_CONTACTO . ' SET
                ' . self::COL_COOP . '   = :id_cooperativa,
                ' . self::COL_NOMBRE_RAW . ' = :nombre,
                ' . self::COL_TITULO . ' = :titulo,
                ' . self::COL_CARGO . '  = :cargo,
                ' . self::COL_TEL . '    = :telefono,
                ' . self::COL_MAIL . '   = :correo,
                ' . self::COL_NOTA . '   = :nota,
                ' . self::COL_FECHA_EVENTO . ' = :fecha_evento
            WHERE ' . self::COL_ID . ' = :id
        ';
        $params = [
            ':id'             => [$id, PDO::PARAM_INT],
            ':id_cooperativa' => [$d['id_cooperativa'] ?? null, PDO::PARAM_INT],
            ':nombre'         => $this->nullableStringParam($d['nombre'] ?? ($d['oficial_nombre'] ?? '')),
            ':titulo'         => $this->nullableStringParam($d['titulo'] ?? ''),
            ':cargo'          => $this->nullableStringParam($d['cargo'] ?? ''),
            ':telefono'       => $this->nullableStringParam($d['telefono_contacto'] ?? ($d['telefono'] ?? '')),
            ':correo'         => $this->nullableStringParam($d['email_contacto'] ?? ($d['oficial_correo'] ?? '')),
            ':nota'           => $this->nullableStringParam($d['nota'] ?? ''),
            ':fecha_evento'   => $this->dateParam($d['fecha_evento'] ?? null),
        ];
        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al actualizar el contacto.', 0, $e);
        }

        $contactoId = null;
        if (isset($d['contacto_cooperativa_id']) && $d['contacto_cooperativa_id']) {
            $contactoId = (int)$d['contacto_cooperativa_id'];
        }
        $contactoId = $this->syncContactoCooperativa($d, $contactoId);
        if ($contactoId !== null) {
            $this->linkAgendaContacto($id, $contactoId);
        }
    }

    /**
     * Elimina un contacto.
     *
     * @param int $id
     */
    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::T_CONTACTO . ' WHERE ' . self::COL_ID . ' = :id';
        try {
            $this->db->execute($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al eliminar el contacto.', 0, $e);
        }

    }

    /**
     * Helper para parámetros de cadenas opcionales.
     *
     * @param mixed $value
     * @return array{0:mixed,1:int}
     */
    private function syncContactoCooperativa(array $data, ?int $existingId): ?int
    {
        $coopId = isset($data['id_cooperativa']) ? (int)$data['id_cooperativa'] : 0;
        $nombre = $this->normalizeScalar($data['nombre'] ?? ($data['oficial_nombre'] ?? null));

        if ($coopId <= 0 || $nombre === null) {
            return $existingId;
        }

        $telefono = $this->normalizeScalar($data['telefono_contacto'] ?? ($data['telefono'] ?? null));
        $correo   = $this->normalizeScalar($data['email_contacto'] ?? ($data['oficial_correo'] ?? null));
        $cargo    = $this->normalizeScalar($data['cargo'] ?? null);

        if ($existingId === null || $existingId <= 0) {
            try {
                $row = $this->db->fetch(
                    'SELECT id_contacto FROM ' . self::T_CONTACTOS_COOP . ' WHERE id_cooperativa = :coop AND lower(nombre_contacto) = lower(:nombre) LIMIT 1',
                    [
                        ':coop'   => [$coopId, PDO::PARAM_INT],
                        ':nombre' => [$nombre, PDO::PARAM_STR],
                    ]
                );
            } catch (\Throwable $e) {
                throw new RuntimeException('Error al consultar el contacto relacionado.', 0, $e);
            }
            if ($row && isset($row['id_contacto'])) {
                $existingId = (int)$row['id_contacto'];
            }
        }

        $params = [
            ':nombre'   => [$nombre, PDO::PARAM_STR],
            ':telefono' => $this->nullableStringParam($telefono),
            ':email'    => $this->nullableStringParam($correo),
            ':cargo'    => $this->nullableStringParam($cargo),
        ];

        if ($existingId !== null && $existingId > 0) {
            $params[':id'] = [$existingId, PDO::PARAM_INT];
            try {
                $this->db->execute(
                    'UPDATE ' . self::T_CONTACTOS_COOP . '
                        SET nombre_contacto = :nombre,
                            telefono = :telefono,
                            email = :email,
                            cargo = :cargo,
                            activo = true,
                            updated_at = NOW()
                      WHERE id_contacto = :id',
                    $params
                );
            } catch (\Throwable $e) {
                throw new RuntimeException('Error al actualizar el contacto principal.', 0, $e);
            }
            return $existingId;
        }

        $insertParams = $params + [
            ':coop' => [$coopId, PDO::PARAM_INT],
        ];

        try {
            $rows = $this->db->execute(
                'INSERT INTO ' . self::T_CONTACTOS_COOP . ' (
                    id_cooperativa, nombre_contacto, telefono, email, cargo, activo, created_at, updated_at
                 ) VALUES (
                    :coop, :nombre, :telefono, :email, :cargo, true, NOW(), NOW()
                 ) RETURNING id_contacto AS id',
                $insertParams
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al registrar el contacto principal.', 0, $e);
        }

        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : null;
        return $row && isset($row['id']) ? (int)$row['id'] : null;
    }

    private function linkAgendaContacto(int $agendaId, ?int $contactoId): void
    {
        if ($agendaId <= 0 || $contactoId === null) {
            return;
        }
        try {
            $this->db->execute(
                'UPDATE ' . self::T_CONTACTO . ' SET ' . self::COL_CONTACTO_ALT . ' = :contacto WHERE ' . self::COL_ID . ' = :id',
                [
                    ':contacto' => [(string)$contactoId, PDO::PARAM_STR],
                    ':id'       => [$agendaId, PDO::PARAM_INT],
                ]
            );
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al asociar el contacto principal.', 0, $e);
        }
    }

    private function extractContactoCoopId($value): ?int
    {
        if ($value === null) {
            return null;
        }
        $raw = trim((string)$value);
        if ($raw === '' || !ctype_digit($raw)) {
            return null;
        }
        return (int)$raw;
    }

    private function normalizeScalar($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $string = trim((string)$value);
        return $string === '' ? null : $string;
    }

    private function nullableStringParam($value): array
    {
        if ($value === null) {
            return [null, PDO::PARAM_NULL];
        }
        $value = (string)$value;
        if ($value === '') {
            return [null, PDO::PARAM_NULL];
        }
        return [$value, PDO::PARAM_STR];
    }

    /**
     * Normaliza fechas a formato Y-m-d; en caso de valor vacío se usa la fecha actual.
     *
     * @param mixed $value
     * @return array{0:string,1:int}
     */
    private function dateParam($value): array
    {
        $date = null;
        if (is_string($value)) {
            $value = trim($value);
            if ($value !== '') {
                $dt = date_create($value);
                if ($dt !== false) {
                    $date = $dt->format('Y-m-d');
                }
            }
        } elseif ($value instanceof \DateTimeInterface) {
            $date = $value->format('Y-m-d');
        }

        if ($date === null) {
            $date = date('Y-m-d');
        }

        return [$date, PDO::PARAM_STR];
    }
}
