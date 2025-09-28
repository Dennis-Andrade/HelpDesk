<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use PDO;

final class EntidadQueryRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function search(string $q, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;
        $term    = trim($q);

        $where  = '';
        $params = [];
        if ($term !== '') {
            $where = 'WHERE (c.nombre ILIKE :like_name OR c.ruc ILIKE :like_ruc)';
            $like  = '%' . $term . '%';
            $params[':like_name'] = $like;
            $params[':like_ruc']  = $like;
        }

        $countSql = 'SELECT COUNT(*) FROM public.cooperativas c ' . $where;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        if ($total === 0) {
            return [
                'items'   => [],
                'total'   => 0,
                'page'    => $page,
                'perPage' => $perPage,
            ];
        }

        $sql = "SELECT
                    c.id_cooperativa AS id,
                    c.nombre,
                    c.ruc,
                    c.tipo_entidad,
                    c.id_segmento,
                    seg.nombre_segmento AS segmento_nombre,
                    c.provincia_id,
                    prov.nombre AS provincia_nombre,
                    c.canton_id,
                    cant.nombre AS canton_nombre,
                    c.telefono AS telefono_legacy,
                    c.telefono_fijo_1,
                    c.telefono_movil,
                    c.email,
                    json_agg(DISTINCT s.nombre ORDER BY s.nombre) AS servicios_json,
                    COUNT(DISTINCT s.id_servicio) AS servicios_count
                FROM public.cooperativas c
                LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
                LEFT JOIN public.provincias prov ON prov.id_provincia = c.provincia_id
                LEFT JOIN public.cantones cant ON cant.id_canton = c.canton_id
                LEFT JOIN public.cooperativa_servicio cs
                       ON cs.id_cooperativa = c.id_cooperativa AND cs.activo = TRUE
                LEFT JOIN public.servicios s ON s.id_servicio = cs.id_servicio
                " . $where . "
                GROUP BY c.id_cooperativa, c.nombre, c.ruc, c.tipo_entidad, c.id_segmento, seg.nombre_segmento,
                         c.provincia_id, prov.nombre, c.canton_id, cant.nombre,
                         c.telefono, c.telefono_fijo_1, c.telefono_movil, c.email
                ORDER BY c.nombre
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $telefonos = $this->sanitizePhones([
                $row['telefono_legacy'] ?? null,
                $row['telefono_fijo_1'] ?? null,
                $row['telefono_movil'] ?? null,
            ]);
            $emails = $this->sanitizeEmails([$row['email'] ?? null]);
            $servicios = $this->decodeJsonList($row['servicios_json'] ?? null);
            $serviciosCount = max(count($servicios), (int)($row['servicios_count'] ?? 0));

            $items[] = [
                'id'               => (int)$row['id'],
                'nombre'           => (string)$row['nombre'],
                'ruc'              => $row['ruc'] !== null ? (string)$row['ruc'] : null,
                'tipo_entidad'     => $row['tipo_entidad'] !== null ? (string)$row['tipo_entidad'] : null,
                'id_segmento'      => $row['id_segmento'] !== null ? (int)$row['id_segmento'] : null,
                'segmento_nombre'  => $row['segmento_nombre'] !== null ? (string)$row['segmento_nombre'] : null,
                'provincia_id'     => $row['provincia_id'] !== null ? (int)$row['provincia_id'] : null,
                'provincia_nombre' => $row['provincia_nombre'] !== null ? (string)$row['provincia_nombre'] : null,
                'canton_id'        => $row['canton_id'] !== null ? (int)$row['canton_id'] : null,
                'canton_nombre'    => $row['canton_nombre'] !== null ? (string)$row['canton_nombre'] : null,
                'telefonos'        => $telefonos,
                'emails'           => $emails,
                'servicios'        => $servicios,
                'servicios_count'  => $serviciosCount,
            ];
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    public function findForEdit(int $id): ?array
    {
        $sql = "SELECT
                    c.id_cooperativa AS id,
                    c.nombre,
                    c.ruc,
                    c.telefono_fijo_1,
                    c.telefono_movil,
                    c.email,
                    c.provincia_id,
                    c.canton_id,
                    c.tipo_entidad,
                    c.id_segmento,
                    c.notas
                FROM public.cooperativas c
                WHERE c.id_cooperativa = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $row['servicios'] = $this->servicioIds($id);
        return $row;
    }

    public function findDetalles(int $id): ?array
    {
        $sql = "SELECT
                    c.id_cooperativa AS id,
                    c.nombre,
                    c.ruc,
                    c.telefono_fijo_1,
                    c.telefono_movil,
                    c.email,
                    c.tipo_entidad,
                    c.id_segmento,
                    seg.nombre_segmento AS segmento_nombre,
                    c.provincia_id,
                    prov.nombre AS provincia,
                    c.canton_id,
                    cant.nombre AS canton,
                    c.notas
                FROM public.cooperativas c
                LEFT JOIN public.segmentos seg ON seg.id_segmento = c.id_segmento
                LEFT JOIN public.provincias prov ON prov.id_provincia = c.provincia_id
                LEFT JOIN public.cantones cant ON cant.id_canton = c.canton_id
                WHERE c.id_cooperativa = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return array<int,array{id:int,nombre:string}> */
    public function segmentos(): array
    {
        $stmt = $this->db->query('SELECT id_segmento AS id, nombre_segmento AS nombre FROM public.segmentos ORDER BY nombre_segmento');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array{id:int,nombre:string}> */
    public function servicios(): array
    {
        $stmt = $this->db->query('SELECT id_servicio AS id, nombre FROM public.servicios ORDER BY nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,array{id:int,nombre:string}> */
    public function serviciosActivos(int $id): array
    {
        $sql = "SELECT s.id_servicio AS id, s.nombre
                FROM public.cooperativa_servicio cs
                JOIN public.servicios s ON s.id_servicio = cs.id_servicio
                WHERE cs.id_cooperativa = :id AND cs.activo = TRUE
                ORDER BY s.nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,int> */
    public function servicioIds(int $id): array
    {
        $sql = "SELECT cs.id_servicio
                FROM public.cooperativa_servicio cs
                WHERE cs.id_cooperativa = :id
                ORDER BY cs.id_servicio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $value) {
            $ids[] = (int)$value;
        }
        return $ids;
    }

    /** @param array<int|string|null> $phones */
    private function sanitizePhones(array $phones): array
    {
        $clean = [];
        foreach ($phones as $phone) {
            if ($phone === null) {
                continue;
            }
            $digits = preg_replace('/\D+/', '', (string)$phone);
            if ($digits === '') {
                continue;
            }
            if (!in_array($digits, $clean, true)) {
                $clean[] = $digits;
            }
        }
        return $clean;
    }

    /** @param array<int|string|null> $emails */
    private function sanitizeEmails(array $emails): array
    {
        $clean = [];
        foreach ($emails as $email) {
            if ($email === null) {
                continue;
            }
            $trimmed = trim((string)$email);
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
     * @param mixed $json
     * @return array<int,string>
     */
    private function decodeJsonList($json): array
    {
        if (!is_string($json) || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $list = [];
        foreach ($decoded as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $trimmed = trim((string)$value);
            if ($trimmed === '') {
                continue;
            }
            if (!in_array($trimmed, $list, true)) {
                $list[] = $trimmed;
            }
        }
        return $list;
    }
}
