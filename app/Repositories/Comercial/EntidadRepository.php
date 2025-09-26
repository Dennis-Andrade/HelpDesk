<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use PDO;
use PDOException;
use function Config\db;

final class EntidadRepository
{
    private PDO $db;
    private const TBL = 'public.cooperativas';
    private const PK  = 'id_cooperativa';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? db();
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function search(string $term, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;
        $term    = trim($term);
        $like    = '%' . $term . '%';

        $countSql = <<<SQL
            SELECT COUNT(*)
            FROM public.cooperativas c
            WHERE (:term = '')
               OR (c.nombre ILIKE :like OR c.ruc ILIKE :like)
        SQL;

        $countSt = $this->db->prepare($countSql);
        $countSt->bindValue(':term', $term, PDO::PARAM_STR);
        $countSt->bindValue(':like', $like, PDO::PARAM_STR);
        $countSt->execute();
        $total = (int)$countSt->fetchColumn();

        if ($total === 0) {
            return [
                'items'   => [],
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        $sql = <<<SQL
            SELECT
                c.id_cooperativa AS id,
                c.nombre,
                NULLIF(c.ruc, '') AS ruc,
                NULLIF(c.telefono_fijo_1, '') AS telefono_fijo_1,
                NULLIF(c.telefono_movil, '') AS telefono_movil,
                NULLIF(c.email, '') AS email,
                c.provincia_id,
                c.canton_id,
                c.tipo_entidad,
                c.id_segmento,
                seg.nombre_segmento AS segmento_nombre,
                prov.nombre AS provincia_nombre,
                cant.nombre AS canton_nombre,
                svc.nombres AS servicios_json,
                COALESCE(svc.total, 0) AS servicios_count
            FROM public.cooperativas c
            LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
            LEFT JOIN public.provincias prov ON prov.id_provincia = c.provincia_id
            LEFT JOIN public.cantones cant ON cant.id_canton = c.canton_id
            LEFT JOIN LATERAL (
                SELECT json_agg(s.nombre_servicio ORDER BY s.nombre_servicio) AS nombres,
                       COUNT(*) AS total
                FROM public.cooperativa_servicio cs
                JOIN public.servicios s ON s.id_servicio = cs.id_servicio
                WHERE cs.id_cooperativa = c.id_cooperativa AND cs.activo = TRUE
            ) svc ON TRUE
            WHERE (:term = '')
               OR (c.nombre ILIKE :like OR c.ruc ILIKE :like)
            ORDER BY c.nombre
            LIMIT :limit OFFSET :offset
        SQL;

        $st = $this->db->prepare($sql);
        $st->bindValue(':term', $term, PDO::PARAM_STR);
        $st->bindValue(':like', $like, PDO::PARAM_STR);
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id'               => (int)$row['id'],
                'nombre'           => (string)$row['nombre'],
                'ruc'              => $row['ruc'] !== null ? (string)$row['ruc'] : null,
                'segmento_nombre'  => $row['segmento_nombre'] !== null ? (string)$row['segmento_nombre'] : null,
                'provincia_nombre' => $row['provincia_nombre'] !== null ? (string)$row['provincia_nombre'] : null,
                'canton_nombre'    => $row['canton_nombre'] !== null ? (string)$row['canton_nombre'] : null,
                'telefonos'        => $this->listFromValues([
                    $row['telefono_fijo_1'] ?? null,
                    $row['telefono_movil'] ?? null,
                ]),
                'emails'           => $this->listFromValues([$row['email'] ?? null]),
                'servicios'        => $this->decodeJsonList($row['servicios_json'] ?? null),
                'servicios_count'  => (int)$row['servicios_count'],
                'tipo_entidad'     => $row['tipo_entidad'] !== null ? (string)$row['tipo_entidad'] : null,
                'id_segmento'      => $row['id_segmento'] !== null ? (int)$row['id_segmento'] : null,
                'provincia_id'     => $row['provincia_id'] !== null ? (int)$row['provincia_id'] : null,
                'canton_id'        => $row['canton_id'] !== null ? (int)$row['canton_id'] : null,
            ];
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    public function findById(int $id): ?array
    {
        $sql = <<<SQL
            SELECT
                c.id_cooperativa AS id,
                c.nombre,
                NULLIF(c.ruc, '') AS ruc,
                NULLIF(c.telefono_fijo_1, '') AS telefono_fijo_1,
                NULLIF(c.telefono_movil, '') AS telefono_movil,
                NULLIF(c.email, '') AS email,
                c.provincia_id,
                c.canton_id,
                c.tipo_entidad,
                c.id_segmento,
                NULLIF(c.notas, '') AS notas
            FROM public.cooperativas c
            WHERE c.id_cooperativa = :id
            LIMIT 1
        SQL;

        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $row['servicios'] = $this->serviciosDeEntidad($id);

        return $row;
    }

    public function findDetalles(int $id): ?array
    {
        $sql = <<<SQL
            SELECT
                c.id_cooperativa AS id_entidad,
                c.nombre,
                NULLIF(c.ruc, '') AS ruc,
                NULLIF(c.telefono_fijo_1, '') AS telefono_fijo_1,
                NULLIF(c.telefono_movil, '') AS telefono_movil,
                NULLIF(c.email, '') AS email,
                c.provincia_id,
                c.canton_id,
                prov.nombre AS provincia,
                cant.nombre AS canton,
                c.tipo_entidad,
                c.id_segmento,
                seg.nombre_segmento AS segmento_nombre,
                NULLIF(c.notas, '') AS notas
            FROM public.cooperativas c
            LEFT JOIN public.provincias prov ON prov.id_provincia = c.provincia_id
            LEFT JOIN public.cantones cant ON cant.id_canton = c.canton_id
            LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
            WHERE c.id_cooperativa = :id
            LIMIT 1
        SQL;

        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @return array<int,array{id_servicio:int,nombre_servicio:string}>
     */
    public function serviciosActivos(int $id): array
    {
        $sql = <<<SQL
            SELECT s.id_servicio, s.nombre_servicio
            FROM public.cooperativa_servicio cs
            JOIN public.servicios s ON s.id_servicio = cs.id_servicio
            WHERE cs.id_cooperativa = :id AND cs.activo = TRUE
            ORDER BY s.nombre_servicio
        SQL;

        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int,array{id:int,nombre:string}>
     */
    public function servicios(): array
    {
        $sql = <<<SQL
            SELECT id_servicio AS id, nombre_servicio AS nombre
            FROM public.servicios
            WHERE activo = TRUE
            ORDER BY nombre_servicio
        SQL;

        $st = $this->db->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int,array{id:int,nombre:string}>
     */
    public function segmentos(): array
    {
        $sql = <<<SQL
            SELECT id_segmento AS id, nombre_segmento AS nombre
            FROM public.segmentos
            ORDER BY id_segmento
        SQL;

        $st = $this->db->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int,int>
     */
    public function serviciosDeEntidad(int $id): array
    {
        $sql = <<<SQL
            SELECT id_servicio
            FROM public.cooperativa_servicio
            WHERE id_cooperativa = :id AND activo = TRUE
            ORDER BY id_servicio
        SQL;

        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $rows ?: []);
    }

    public function create(array $d): int
    {
        $sql = <<<SQL
            INSERT INTO public.cooperativas
                (nombre, ruc, telefono_fijo_1, telefono_movil, email,
                 provincia_id, canton_id, tipo_entidad, id_segmento, notas)
            VALUES
                (:nombre, :ruc, :tfijo, :tmovil, :email,
                 :prov, :canton, :tipo, :seg, :notas)
            RETURNING id_cooperativa AS id
        SQL;

        $st = $this->db->prepare($sql);
        $this->bindEntidadCommon($st, $d);
        $st->execute();
        $id = $st->fetchColumn();
        return (int)$id;
    }

    public function update(int $id, array $d): void
    {
        $sql = <<<SQL
            UPDATE public.cooperativas SET
                nombre = :nombre,
                ruc = :ruc,
                telefono_fijo_1 = :tfijo,
                telefono_movil = :tmovil,
                email = :email,
                provincia_id = :prov,
                canton_id = :canton,
                tipo_entidad = :tipo,
                id_segmento = :seg,
                notas = :notas
            WHERE id_cooperativa = :id
        SQL;

        $st = $this->db->prepare($sql);
        $this->bindEntidadCommon($st, $d);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    public function replaceServicios(int $id, array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (in_array(1, $ids, true)) {
            $ids = [1];
        }

        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare('DELETE FROM public.cooperativa_servicio WHERE id_cooperativa = :id');
            $del->bindValue(':id', $id, PDO::PARAM_INT);
            $del->execute();

            if ($ids !== []) {
                $ins = $this->db->prepare(
                    'INSERT INTO public.cooperativa_servicio (id_cooperativa, id_servicio, activo, fecha_alta)
                     VALUES (:id, :sid, TRUE, now())'
                );
                foreach ($ids as $sid) {
                    $ins->bindValue(':id', $id, PDO::PARAM_INT);
                    $ins->bindValue(':sid', $sid, PDO::PARAM_INT);
                    $ins->execute();
                }
            }

            $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM public.cooperativas WHERE id_cooperativa = :id');
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    private function bindEntidadCommon(\PDOStatement $st, array $d): void
    {
        $st->bindValue(':nombre', (string)$d['nombre'], PDO::PARAM_STR);
        $this->bindNullableString($st, ':ruc', $d['ruc'] ?? null);
        $this->bindNullableString($st, ':tfijo', $d['telefono_fijo_1'] ?? null);
        $this->bindNullableString($st, ':tmovil', $d['telefono_movil'] ?? null);
        $this->bindNullableString($st, ':email', $d['email'] ?? null);
        $this->bindNullableInt($st, ':prov', $d['provincia_id'] ?? null);
        $this->bindNullableInt($st, ':canton', $d['canton_id'] ?? null);
        $this->bindNullableString($st, ':tipo', $d['tipo_entidad'] ?? null);
        $this->bindNullableInt($st, ':seg', $d['id_segmento'] ?? null);
        $this->bindNullableString($st, ':notas', $d['notas'] ?? null);
    }

    private function bindNullableInt(\PDOStatement $st, string $param, ?int $value): void
    {
        if ($value === null) {
            $st->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $st->bindValue($param, $value, PDO::PARAM_INT);
    }

    private function bindNullableString(\PDOStatement $st, string $param, ?string $value): void
    {
        if ($value === null || $value === '') {
            $st->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $st->bindValue($param, $value, PDO::PARAM_STR);
    }

    /**
     * @param mixed $json
     * @return array<int,string>
     */
    private function decodeJsonList($json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        if (is_array($json)) {
            $values = $json;
        } else {
            $decoded = json_decode((string)$json, true);
            $values  = is_array($decoded) ? $decoded : [];
        }

        return $this->listFromValues($values);
    }

    /**
     * @param array<int,mixed> $values
     * @return array<int,string>
     */
    private function listFromValues(array $values): array
    {
        $list = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $trimmed = trim((string)$value);
            if ($trimmed === '' || in_array($trimmed, $list, true)) {
                continue;
            }
            $list[] = $trimmed;
        }

        return $list;
    }
}
