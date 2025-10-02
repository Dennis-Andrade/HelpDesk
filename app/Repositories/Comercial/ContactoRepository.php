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

    /** @var string Tabla de cooperativas. */
    private const T_COOP          = 'public.cooperativas';
    private const COL_COOP_ID     = 'id_cooperativa';
    private const COL_COOP_NOMBRE = 'nombre';

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
                c.' . self::COL_NOTA . ' AS nota
            FROM ' . self::T_CONTACTO . ' c
            INNER JOIN ' . self::T_COOP . ' e
                ON e.' . self::COL_COOP_ID . ' = c.' . self::COL_COOP . '
            WHERE (
                :has_q = 0
                OR unaccent(lower(' . $nombreExpr . ')) LIKE unaccent(lower(:like))
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
                ' . self::COL_NOTA . '
            ) VALUES (
                :id_cooperativa,
                :nombre,
                :titulo,
                :cargo,
                :telefono,
                :correo,
                :nota
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
        return (int)$row['id'];
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
                ' . self::COL_NOTA . '   = :nota
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
        ];
        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al actualizar el contacto.', 0, $e);
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
}
