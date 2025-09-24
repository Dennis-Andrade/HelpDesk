<?php
namespace App\Repositories\Comercial;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use function Config\db;

/**
 * Repositorio de Cooperativas (Comercial).
 * - Bindea tipos correctamente (STR/NULL, INT/NULL, BOOL)
 * - Provee helpers de segmentos, servicios y pivot de relación
 * - SELECT con alias que el formulario espera (ruc AS nit, estado)
 */
final class EntidadRepository
{
    /** === ESQUEMA / MAPEOS (ajusta si tu DB usa otros nombres) ===================== */
    private const T_COOP            = 'public.cooperativas';
    private const COL_ID            = 'id_cooperativa';      // PK cooperativas
    private const COL_NOMBRE        = 'nombre';
    private const COL_RUC           = 'ruc';
    private const COL_TELF          = 'telefono';  
    private const COL_TFIJ          = 'telefono_fijo_1';  // (si existe en tu schema)
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

    /** ================================================================================= */
    /** Búsqueda paginada */
    /** ================================================================================= */
    /**
     * Búsqueda paginada
     * @return array{items:array, total:int, page:int, perPage:int}
     */
    public function search(string $q, int $page, int $perPage): array
    {
        $pdo = db();

        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $q      = trim($q);
        $hasQ   = $q !== '' ? 1 : 0;
        $qLike  = '%' . $q . '%';

        $countSql = "
            SELECT COUNT(*)
            FROM " . self::T_COOP . " c
            WHERE (
                :has_q = 0
                OR (
                    unaccent(lower(c." . self::COL_NOMBRE . ")) LIKE unaccent(lower(:q_like))
                    OR c." . self::COL_RUC . " LIKE :q_like
                )
            )
        ";

        $stTotal = $pdo->prepare($countSql);
        $this->bindSearchTerms($stTotal, $hasQ, $qLike);

        try {
            $stTotal->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al contar entidades.', 0, $e);
        }

        $total = (int) $stTotal->fetchColumn();

        if ($total === 0) {
            return [
                'items'   => [],
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        $sql = "
            SELECT
              c." . self::COL_ID . "          AS id_entidad,
              c." . self::COL_NOMBRE . "      AS nombre,
              c." . self::COL_RUC . "         AS ruc,
              c." . self::COL_TELF . "        AS telefono,
              c." . self::COL_TFIJ . "       AS telefono_fijo_1,
              c." . self::COL_TMOV . "        AS telefono_movil,
              c." . self::COL_MAIL . "        AS email,
              c." . self::COL_TIPO . "        AS tipo_entidad,
              c." . self::COL_SEGMENTO . "    AS id_segmento,
              seg." . self::COL_NOM_SEG . "    AS segmento_nombre,
              c." . self::COL_PROV . "        AS provincia_id,
              prov.nombre                       AS provincia,
              c." . self::COL_CANTON . "      AS canton_id,
              can.nombre                        AS canton,
              c." . self::COL_NOTAS . "        AS notas
            FROM " . self::T_COOP . " c
            LEFT JOIN " . self::T_SEG . "       seg ON seg." . self::COL_ID_SEG . " = c." . self::COL_SEGMENTO . "
            LEFT JOIN public.provincia         prov ON prov.id = c." . self::COL_PROV . "
            LEFT JOIN public.canton            can  ON can.id = c." . self::COL_CANTON . "
            WHERE (
                :has_q = 0
                OR (
                    unaccent(lower(c." . self::COL_NOMBRE . ")) LIKE unaccent(lower(:q_like))
                    OR c." . self::COL_RUC . " LIKE :q_like
                )
            )
            ORDER BY c." . self::COL_NOMBRE . " ASC
            LIMIT :limit OFFSET :offset
        ";

        $st = $pdo->prepare($sql);
        $this->bindSearchTerms($st, $hasQ, $qLike);
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al buscar entidades.', 0, $e);
        }

        $items = $st->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'items'   => [],
                'total'   => $total,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        $servicios = $this->serviciosParaListado($pdo, $items);

        foreach ($items as &$item) {
            $id = (int)($item['id_entidad'] ?? 0);
            $item['segmento']  = isset($item['segmento_nombre']) ? (string)$item['segmento_nombre'] : '';
            $item['servicios'] = $servicios[$id] ?? [];
        }
        unset($item);

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /** Datos para editar */
    public function findById(int $id): ?array
    {
        $sql = "
            SELECT
                ".self::COL_ID."    AS id_cooperativa,
                ".self::COL_NOMBRE."     AS nombre,
                ".self::COL_RUC."        AS nit,
                ".self::COL_TFIJ."      AS telefono_fijo_1,
                ".self::COL_TMOV."       AS telefono_movil,
                ".self::COL_MAIL."      AS email,
                ".self::COL_PROV."       AS provincia_id,
                ".self::COL_CANTON."     AS canton_id,
                ".self::COL_TIPO."       AS tipo_entidad,
                ".self::COL_SEGMENTO."   AS id_segmento,
                ".self::COL_NOTAS."      AS notas,
                ".self::COL_ACTV."     AS activa,
                CASE WHEN ".self::COL_ACTV." THEN 'activo' ELSE 'inactivo' END AS estado
            FROM ".self::T_COOP."
            WHERE ".self::COL_ID." = :id
            LIMIT 1
        ";
        $st = db()->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener la entidad.', 0, $e);
        }

        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function findDetalles(int $id): ?array
    {
        $sql = "
        SELECT
            c.id_cooperativa              AS id_entidad,
            c.nombre,
            c.ruc,
            c.telefono_fijo_1,
            c.telefono_movil,
            c.email,
            c.provincia_id,
            c.canton_id,
            p.nombre                      AS provincia,
            ct.nombre                     AS canton,
            c.tipo_entidad,
            c.id_segmento,
            seg.nombre_segmento           AS segmento_nombre,
            c.notas
        FROM public.cooperativas c
        LEFT JOIN public.provincia p ON p.id = c.provincia_id
        LEFT JOIN public.canton   ct ON ct.id = c.canton_id
        LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
        WHERE c.id_cooperativa = :id
        LIMIT 1
        ";
        $st = db()->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener el detalle de la entidad.', 0, $e);
        }

        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function serviciosActivos(int $id): array
    {
        $sql = "
        SELECT s.id_servicio, s.nombre_servicio
        FROM public.cooperativa_servicio cs
        JOIN public.servicios s ON s.id_servicio = cs.id_servicio
        WHERE cs.id_cooperativa = :id AND cs.activo = true
        ORDER BY s.nombre_servicio
        ";
        $st = db()->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener los servicios activos.', 0, $e);
        }

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


    /** Crear y devolver el id nuevo */
    public function create(array $d): int
    {
        $sql = "
            INSERT INTO ".self::T_COOP."
                ( ".self::COL_NOMBRE.",
                  ".self::COL_RUC.",
                  ".self::COL_TFIJ.",
                  ".self::COL_TMOV.",
                  ".self::COL_MAIL.",
                  ".self::COL_PROV.",
                  ".self::COL_CANTON.",
                  ".self::COL_TIPO.",
                  ".self::COL_SEGMENTO.",
                  ".self::COL_NOTAS.",
                  ".self::COL_ACTV."
                )
            VALUES
                ( :nombre, :ruc, :tfijo, :tmov, :email, :prov, :canton, :tipo, :segmento, :notas, :activa )
            RETURNING ".self::COL_ID."
        ";
        $st = db()->prepare($sql);

        // STR o NULL
        $st->bindValue(':nombre', $d['nombre']);
        $st->bindValue(':ruc',     $d['nit']            !== '' ? $d['nit']            : null, $d['nit']            !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':tfijo',   $d['telefono_fijo']  !== '' ? $d['telefono_fijo']  : null, $d['telefono_fijo']  !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':tmov',    $d['telefono_movil'] !== '' ? $d['telefono_movil'] : null, $d['telefono_movil'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':email',   $d['email']          !== '' ? $d['email']          : null, $d['email']          !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        // INT o NULL
        $st->bindValue(':prov',   $d['provincia_id'], $d['provincia_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(':canton', $d['canton_id'],    $d['canton_id']    !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);

        // STR
        $st->bindValue(':tipo', $d['tipo_entidad']);

        // INT o NULL
        $st->bindValue(':segmento', $d['id_segmento'], $d['id_segmento'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);

        // STR o NULL
        $st->bindValue(':notas', $d['notas'] !== '' ? $d['notas'] : null, $d['notas'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        // BOOL real
        $activa = (bool)($d['activa'] ?? (($d['estado'] ?? 'activo') === 'activo'));
        $st->bindValue(':activa', $activa, PDO::PARAM_BOOL);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al crear la entidad.', 0, $e);
        }

        return (int) $st->fetchColumn();
    }

    /** Actualizar */
    public function update(int $id, array $d): void
    {
        $sql = "
            UPDATE ".self::T_COOP." SET
                ".self::COL_NOMBRE."   = :nombre,
                ".self::COL_RUC."      = :ruc,
                ".self::COL_TFIJ."    = :tfijo,
                ".self::COL_TMOV."     = :tmov,
                ".self::COL_MAIL."    = :email,
                ".self::COL_PROV."     = :prov,
                ".self::COL_CANTON."   = :canton,
                ".self::COL_TIPO."     = :tipo,
                ".self::COL_SEGMENTO." = :segmento,
                ".self::COL_NOTAS."    = :notas,
                ".self::COL_ACTV."   = :activa
            WHERE ".self::COL_ID." = :id
        ";
        $st = db()->prepare($sql);

        // STR o NULL
        $st->bindValue(':nombre', $d['nombre']);
        $st->bindValue(':ruc',     $d['nit']            !== '' ? $d['nit']            : null, $d['nit']            !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':tfijo',   $d['telefono_fijo']  !== '' ? $d['telefono_fijo']  : null, $d['telefono_fijo']  !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':tmov',    $d['telefono_movil'] !== '' ? $d['telefono_movil'] : null, $d['telefono_movil'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $st->bindValue(':email',   $d['email']          !== '' ? $d['email']          : null, $d['email']          !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        // INT o NULL
        $st->bindValue(':prov',   $d['provincia_id'], $d['provincia_id'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $st->bindValue(':canton', $d['canton_id'],    $d['canton_id']    !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);

        // STR
        $st->bindValue(':tipo', $d['tipo_entidad']);

        // INT o NULL
        $st->bindValue(':segmento', $d['id_segmento'], $d['id_segmento'] !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);

        // STR o NULL
        $st->bindValue(':notas', $d['notas'] !== '' ? $d['notas'] : null, $d['notas'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

        // BOOL real
        $activa = (bool)($d['activa'] ?? (($d['estado'] ?? 'activo') === 'activo'));
        $st->bindValue(':activa', $activa, PDO::PARAM_BOOL);

        $st->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al actualizar la entidad.', 0, $e);
        }
    }

    /** Eliminar */
    public function delete(int $id): void
    {
        $st = db()->prepare("DELETE FROM " . self::T_COOP . " WHERE " . self::COL_ID . " = :id");
        $st->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al eliminar la entidad.', 0, $e);
        }
    }

    /** Catálogo de servicios activos */
    public function servicios(): array
    {
        $sql = "SELECT " . self::COL_ID_SERV . " AS id_servicio, " . self::COL_NOM_SERV . " AS nombre_servicio
                FROM " . self::T_SERV . "
                WHERE " . self::COL_SERV_ACTIVO . " = true
                ORDER BY " . self::COL_ID_SERV;
        $st = db()->prepare($sql);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener los servicios.', 0, $e);
        }

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Catálogo de segmentos (1..5) */
    public function segmentos(): array
    {
        $sql = "SELECT " . self::COL_ID_SEG . " AS id_segmento, " . self::COL_NOM_SEG . " AS nombre_segmento
                FROM " . self::T_SEG . "
                ORDER BY " . self::COL_ID_SEG;
        $st = db()->prepare($sql);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener los segmentos.', 0, $e);
        }

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** IDs de servicios asignados a una entidad */
    public function serviciosDeEntidad(int $id): array
    {
        $sql = "SELECT " . self::PIV_SERV . " FROM " . self::T_PIVOT . " WHERE " . self::PIV_COOP . " = :id";
        $st  = db()->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);

        try {
            $st->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener los servicios de la entidad.', 0, $e);
        }

        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<int,string>>
     */
    private function serviciosParaListado(PDO $pdo, array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int)($row['id_entidad'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }

        if (empty($ids)) {
            return [];
        }

        $placeholders = [];
        $bindings     = [];
        $i = 0;
        foreach (array_keys($ids) as $id) {
            $placeholder     = ':id' . $i++;
            $placeholders[]  = $placeholder;
            $bindings[$placeholder] = $id;
        }

        $sql = "
            SELECT
                cs." . self::PIV_COOP . "  AS id_entidad,
                s." . self::COL_NOM_SERV . " AS nombre_servicio
            FROM " . self::T_PIVOT . " cs
            JOIN " . self::T_SERV . " s ON s." . self::COL_ID_SERV . " = cs." . self::PIV_SERV . "
            WHERE cs." . self::PIV_COOP . " IN (" . implode(',', $placeholders) . ")
              AND cs." . self::PIV_ACTIVO . " = true
            ORDER BY s." . self::COL_NOM_SERV . "
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($bindings as $placeholder => $value) {
            $stmt->bindValue($placeholder, $value, PDO::PARAM_INT);
        }

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener los servicios del listado.', 0, $e);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $id = (int)($row['id_entidad'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $label = isset($row['nombre_servicio']) ? trim((string)$row['nombre_servicio']) : '';
            if ($label === '') {
                continue;
            }
            if (!isset($map[$id])) {
                $map[$id] = [];
            }
            $map[$id][] = $label;
        }

        return $map;
    }

    private function bindSearchTerms(PDOStatement $stmt, int $hasQ, string $qLike): void
    {
        $stmt->bindValue(':has_q', $hasQ, PDO::PARAM_INT);
        $stmt->bindValue(':q_like', $qLike, PDO::PARAM_STR);
    }

    /** Reemplazar relación servicios (Matrix=1 exclusivo) */
    public function replaceServicios(int $id, array $ids): void
    {
        // Limpia y única
        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Regla: si Matrix (1) está, es exclusivo
        if (in_array(1, $ids, true)) {
            $ids = [1];
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM " . self::T_PIVOT . " WHERE " . self::PIV_COOP . " = :id");
            $del->bindValue(':id', $id, PDO::PARAM_INT);
            $del->execute();

            if (!empty($ids)) {
                $ins = $pdo->prepare("
                    INSERT INTO " . self::T_PIVOT . " (" . self::PIV_COOP . ", " . self::PIV_SERV . ", " . self::PIV_ACTIVO . ")
                    VALUES (:c, :s, true)
                ");
                foreach ($ids as $sid) {
                    $ins->bindValue(':c', $id, PDO::PARAM_INT);
                    $ins->bindValue(':s', $sid, PDO::PARAM_INT);
                    $ins->execute();
                }
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw new RuntimeException('Error al actualizar los servicios de la entidad.', 0, $e);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
