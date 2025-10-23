<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

final class ContratoRepository extends BaseRepository
{
    private const TABLE = 'public.contrataciones_servicios';
    private const COOP_TABLE = 'public.cooperativas';
    private const SERV_TABLE = 'public.servicios';
    private const RED_TABLE = 'public.red';
    private const COOP_RED_TABLE = 'public.cooperativa_red';
    private const PIVOT_TABLE = 'public.contrato_servicios_detalle';

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $params = [];
        $joins = $this->buildJoins();
        $where = $this->buildFilters($filters, $params);

        $countSql = 'SELECT COUNT(*) AS total FROM ' . self::TABLE . ' cs' . $joins . $where;

        try {
            $row = $this->db->fetch($countSql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar contratos.', 0, $e);
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

        $params[':limit'] = [$perPage, PDO::PARAM_INT];
        $params[':offset'] = [$offset, PDO::PARAM_INT];

        $table = self::TABLE;
        $pivot = self::PIVOT_TABLE;
        $servTable = self::SERV_TABLE;

        $sql = <<<SQL
SELECT
    cs.id_contratacion AS id,
    cs.id_cooperativa,
    coop.nombre AS cooperativa,
    cs.id_servicio,
    serv.nombre_servicio AS servicio_principal,
    cs.fecha_contratacion,
    cs.fecha_caducidad,
    cs.fecha_desvinculacion,
    cs.fecha_finalizacion,
    cs.periodo_facturacion,
    cs.tipo_contrato,
    cs.terminacion_contrato,
    cs.estado_pago,
    cs.activo,
    cs.valor_contratado,
    cs.valor_individual,
    cs.valor_grupal,
    cs.valor_iva,
    cs.valor_total,
    cs.codigo_red,
    COALESCE(red.nombre, redFallback.nombre) AS red_nombre,
    cs.numero_licencias,
    cs.fecha_ultimo_pago,
    cs.documento_contable,
    cs.observaciones,
    svc.servicios_label,
    svc.servicios_json,
    svc.servicios_ids_json,
    hist.history_count,
    hist.last_estado
FROM {$table} cs{$joins}
LEFT JOIN LATERAL (
    SELECT
        string_agg(s.nombre_servicio, ', ' ORDER BY s.nombre_servicio) AS servicios_label,
        json_agg(json_build_object('id', ps.id_servicio, 'nombre', s.nombre_servicio) ORDER BY s.nombre_servicio) AS servicios_json,
        json_agg(ps.id_servicio ORDER BY s.nombre_servicio) AS servicios_ids_json
    FROM {$pivot} ps
    JOIN {$servTable} s ON s.id_servicio = ps.id_servicio
    WHERE ps.id_contratacion = cs.id_contratacion
) AS svc ON TRUE
LEFT JOIN LATERAL (
    SELECT COUNT(*) AS history_count,
           (ARRAY_AGG(h.estado ORDER BY h.fecha_emision DESC, h.id DESC))[1] AS last_estado
    FROM public.contabilidad_facturacion_historial h
    WHERE h.id_contratacion = cs.id_contratacion
) AS hist ON TRUE
{$where}
ORDER BY coop.nombre ASC, COALESCE(svc.servicios_label, serv.nombre_servicio) ASC
LIMIT :limit OFFSET :offset
SQL;

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener contratos.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapContrato($row);
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $table = self::TABLE;
        $coopTable = self::COOP_TABLE;
        $servTable = self::SERV_TABLE;
        $redTable = self::RED_TABLE;
        $pivot = self::PIVOT_TABLE;

        $sql = <<<SQL
SELECT
    cs.*,
    coop.nombre AS cooperativa,
    serv.nombre_servicio AS servicio_nombre,
    red.nombre AS red_nombre,
    svc.servicios_label,
    svc.servicios_json,
    svc.servicios_ids_json
FROM {$table} cs
LEFT JOIN {$coopTable} coop ON coop.id_cooperativa = cs.id_cooperativa
LEFT JOIN {$servTable} serv ON serv.id_servicio = cs.id_servicio
LEFT JOIN {$redTable} red ON red.codigo = cs.codigo_red
LEFT JOIN LATERAL (
    SELECT
        string_agg(s.nombre_servicio, ', ' ORDER BY s.nombre_servicio) AS servicios_label,
        json_agg(json_build_object('id', ps.id_servicio, 'nombre', s.nombre_servicio) ORDER BY s.nombre_servicio) AS servicios_json,
        json_agg(ps.id_servicio ORDER BY s.nombre_servicio) AS servicios_ids_json
    FROM {$pivot} ps
    JOIN {$servTable} s ON s.id_servicio = ps.id_servicio
    WHERE ps.id_contratacion = cs.id_contratacion
) AS svc ON TRUE
WHERE cs.id_contratacion = :id
LIMIT 1
SQL;

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el contrato solicitado.', 0, $e);
        }

        return $row ? $this->mapContrato($row) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    id_cooperativa,
                    id_servicio,
                    fecha_contratacion,
                    fecha_caducidad,
                    fecha_desvinculacion,
                    fecha_finalizacion,
                    periodo_facturacion,
                    tipo_contrato,
                    terminacion_contrato,
                    estado_pago,
                    activo,
                    valor_contratado,
                    valor_individual,
                    valor_grupal,
                    valor_iva,
                    valor_total,
                    numero_licencias,
                    fecha_ultimo_pago,
                    documento_contable,
                    observaciones,
                    codigo_red
                ) VALUES (
                    :cooperativa,
                    :servicio,
                    :fecha_contratacion,
                    :fecha_caducidad,
                    :fecha_desvinculacion,
                    :fecha_finalizacion,
                    :periodo,
                    :tipo_contrato,
                    :terminacion_contrato,
                    :estado,
                    :activo,
                    :valor_base,
                    :valor_individual,
                    :valor_grupal,
                    :valor_iva,
                    :valor_total,
                    :licencias,
                    :fecha_ultimo_pago,
                    :documento,
                    :observaciones,
                    :codigo_red
                ) RETURNING id_contratacion';

        $params = $this->buildPersistenceParams($data);

        $id = 0;
        $this->db->begin();
        try {
            $rows = $this->db->execute($sql, $params);
            $row = is_array($rows) && isset($rows[0]['id_contratacion']) ? $rows[0] : null;
            $id  = $row ? (int)$row['id_contratacion'] : 0;

            if ($id > 0) {
                $servicios = $data['servicios_ids'] ?? [];
                if (empty($servicios) && isset($data['id_servicio'])) {
                    $servicios = [(int)$data['id_servicio']];
                }
                $this->syncServicios($id, $servicios);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo registrar el contrato.', 0, $e);
        }

        return $id;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET
                    id_cooperativa = :cooperativa,
                    id_servicio = :servicio,
                    fecha_contratacion = :fecha_contratacion,
                    fecha_caducidad = :fecha_caducidad,
                    fecha_desvinculacion = :fecha_desvinculacion,
                    fecha_finalizacion = :fecha_finalizacion,
                    periodo_facturacion = :periodo,
                    tipo_contrato = :tipo_contrato,
                    terminacion_contrato = :terminacion_contrato,
                    estado_pago = :estado,
                    activo = :activo,
                    valor_contratado = :valor_base,
                    valor_individual = :valor_individual,
                    valor_grupal = :valor_grupal,
                    valor_iva = :valor_iva,
                    valor_total = :valor_total,
                    numero_licencias = :licencias,
                    fecha_ultimo_pago = :fecha_ultimo_pago,
                    documento_contable = :documento,
                    observaciones = :observaciones,
                    codigo_red = :codigo_red
                WHERE id_contratacion = :id';

        $params = $this->buildPersistenceParams($data);
        $params[':id'] = [$id, PDO::PARAM_INT];

        $this->db->begin();
        try {
            $this->db->execute($sql, $params);
            $servicios = $data['servicios_ids'] ?? [];
            if (empty($servicios) && isset($data['id_servicio'])) {
                $servicios = [(int)$data['id_servicio']];
            }
            $this->syncServicios($id, $servicios);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo actualizar el contrato.', 0, $e);
        }
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id_contratacion = :id';

        try {
            $this->db->execute($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo eliminar el contrato.', 0, $e);
        }
    }

    /**
     * @return array<int,array{id:int,nombre:string}>
     */
    public function servicios(): array
    {
        try {
            $rows = $this->db->fetchAll('SELECT id_servicio AS id, nombre_servicio AS nombre FROM ' . self::SERV_TABLE . ' WHERE activo = true ORDER BY nombre_servicio ASC');
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudieron obtener los servicios.', 0, $e);
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
     * @return array<int,array{codigo:string,nombre:string}>
     */
    public function redes(): array
    {
        try {
            $rows = $this->db->fetchAll('SELECT codigo, nombre FROM ' . self::RED_TABLE . ' ORDER BY nombre ASC');
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudieron obtener las redes.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            if (!isset($row['codigo'])) {
                continue;
            }
            $items[] = [
                'codigo' => (string)$row['codigo'],
                'nombre' => isset($row['nombre']) ? (string)$row['nombre'] : (string)$row['codigo'],
            ];
        }

        return $items;
    }

    /**
     * @return array<int,string>
     */
    public function estadosPago(): array
    {
        return ['PENDIENTE', 'PAGADO', 'VENCIDO', 'ANULADO'];
    }

    private function buildFilters(array $filters, array &$params): string
    {
        $conditions = [];

        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($q !== '') {
            $conditions[] = '(
                unaccent(lower(coop.nombre)) LIKE unaccent(lower(:q_like))
                OR unaccent(lower(serv.nombre_servicio)) LIKE unaccent(lower(:q_like))
                OR EXISTS (
                    SELECT 1
                      FROM ' . self::PIVOT_TABLE . ' psq
                      JOIN ' . self::SERV_TABLE . ' sq ON sq.id_servicio = psq.id_servicio
                     WHERE psq.id_contratacion = cs.id_contratacion
                       AND unaccent(lower(sq.nombre_servicio)) LIKE unaccent(lower(:q_like))
                )
            )';
            $params[':q_like'] = ['%' . $q . '%', PDO::PARAM_STR];
        }

        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $conditions[] = 'cs.estado_pago = :estado';
            $params[':estado'] = [$filters['estado'], PDO::PARAM_STR];
        }

        if (isset($filters['servicio']) && $filters['servicio'] !== '') {
            $conditions[] = '(
                cs.id_servicio = :servicio
                OR EXISTS (
                    SELECT 1 FROM ' . self::PIVOT_TABLE . ' psf
                    WHERE psf.id_contratacion = cs.id_contratacion AND psf.id_servicio = :servicio
                )
            )';
            $params[':servicio'] = [(int)$filters['servicio'], PDO::PARAM_INT];
        }

        if (isset($filters['red']) && $filters['red'] !== '') {
            $conditions[] = '(cs.codigo_red = :red OR cr.codigo_red = :red)';
            $params[':red'] = [$filters['red'], PDO::PARAM_STR];
        }

        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $conditions[] = 'cs.activo = :activo';
            $params[':activo'] = [$filters['activo'] === '1', PDO::PARAM_BOOL];
        }

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function buildJoins(): string
    {
        return ' LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = cs.id_cooperativa'
            . ' LEFT JOIN ' . self::SERV_TABLE . ' serv ON serv.id_servicio = cs.id_servicio'
            . ' LEFT JOIN ' . self::RED_TABLE . ' red ON red.codigo = cs.codigo_red'
            . ' LEFT JOIN ' . self::COOP_RED_TABLE . ' cr ON cr.id_cooperativa = cs.id_cooperativa'
            . ' LEFT JOIN ' . self::RED_TABLE . ' redFallback ON redFallback.codigo = cr.codigo_red';
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapContrato(array $row): array
    {
        $valorBase = isset($row['valor_contratado']) ? (float)$row['valor_contratado'] : 0.0;
        $valorIva  = isset($row['valor_iva']) ? (float)$row['valor_iva'] : round($valorBase * 0.15, 2);
        $valorTotal = isset($row['valor_total']) ? (float)$row['valor_total'] : round($valorBase + $valorIva, 2);

        $serviciosDetalle = $this->decodeServiciosJson($row['servicios_json'] ?? null);
        if (empty($serviciosDetalle) && isset($row['id_servicio']) && (int)$row['id_servicio'] > 0) {
            $nombrePrincipal = (string)($row['servicios_label'] ?? $row['servicio_principal'] ?? $row['servicio_nombre'] ?? '');
            $serviciosDetalle[] = [
                'id'     => (int)$row['id_servicio'],
                'nombre' => $nombrePrincipal,
            ];
        }

        $serviciosIds = array_values(array_unique(array_map(static function ($svc) {
            return isset($svc['id']) ? (int)$svc['id'] : 0;
        }, $serviciosDetalle)));
        $serviciosIds = array_values(array_filter($serviciosIds, static fn($id) => $id > 0));

        $serviciosNombres = array_values(array_filter(array_map(static function ($svc) {
            return isset($svc['nombre']) ? trim((string)$svc['nombre']) : '';
        }, $serviciosDetalle), static fn($nombre) => $nombre !== ''));

        $servicioLabel = (string)($row['servicios_label'] ?? '');
        if ($servicioLabel === '' && !empty($serviciosNombres)) {
            $servicioLabel = implode(', ', $serviciosNombres);
        }
        if ($servicioLabel === '') {
            $servicioLabel = (string)($row['servicio_principal'] ?? $row['servicio_nombre'] ?? '');
        }

        return [
            'id'                 => (int)($row['id'] ?? $row['id_contratacion'] ?? 0),
            'id_cooperativa'     => (int)($row['id_cooperativa'] ?? 0),
            'cooperativa'        => (string)($row['cooperativa'] ?? ''),
            'id_servicio'        => isset($row['id_servicio']) ? (int)$row['id_servicio'] : null,
            'servicio'           => $servicioLabel,
            'servicios'          => $serviciosNombres,
            'servicios_ids'      => $serviciosIds,
            'servicios_detalle'  => $serviciosDetalle,
            'fecha_contratacion' => $this->normalizeDate($row['fecha_contratacion'] ?? null),
            'fecha_caducidad'    => $this->normalizeDate($row['fecha_caducidad'] ?? null),
            'fecha_desvinculacion'=> $this->normalizeDate($row['fecha_desvinculacion'] ?? null),
            'fecha_finalizacion' => $this->normalizeDate($row['fecha_finalizacion'] ?? null),
            'terminacion_contrato'=> (string)($row['terminacion_contrato'] ?? ''),
            'periodo_facturacion'=> (string)($row['periodo_facturacion'] ?? ''),
            'periodo'            => (string)($row['periodo_facturacion'] ?? ''),
            'tipo_contrato'      => (string)($row['tipo_contrato'] ?? ''),
            'estado_pago'        => (string)($row['estado_pago'] ?? ''),
            'activo'             => isset($row['activo']) ? (bool)$row['activo'] : true,
            'valor_base'         => $valorBase,
            'valor_contratado'   => $valorBase,
            'valor_individual'   => isset($row['valor_individual']) ? (float)$row['valor_individual'] : null,
            'valor_grupal'       => isset($row['valor_grupal']) ? (float)$row['valor_grupal'] : null,
            'valor_iva'          => $valorIva,
            'valor_total'        => $valorTotal,
            'iva_porcentaje'     => $valorBase > 0 ? round(($valorIva / $valorBase) * 100, 2) : 15,
            'numero_licencias'   => isset($row['numero_licencias']) ? (int)$row['numero_licencias'] : null,
            'fecha_ultimo_pago'  => $this->normalizeDate($row['fecha_ultimo_pago'] ?? null),
            'documento_contable' => (string)($row['documento_contable'] ?? ''),
            'observaciones'      => (string)($row['observaciones'] ?? ''),
            'codigo_red'         => isset($row['codigo_red']) ? (string)$row['codigo_red'] : null,
            'red_nombre'         => (string)($row['red_nombre'] ?? ''),
            'historial_count'    => isset($row['history_count']) ? (int)$row['history_count'] : 0,
            'historial_estado'   => isset($row['last_estado']) ? (string)$row['last_estado'] : '',
        ];
    }

    /**
     * @param mixed $json
     * @return array<int,array{id:int,nombre:string}>
     */
    private function decodeServiciosJson($json): array
    {
        if ($json === null) {
            return [];
        }

        if (is_string($json) && $json !== '') {
            $decoded = json_decode($json, true);
        } elseif (is_array($json)) {
            $decoded = $json;
        } else {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $id = null;
            $nombre = null;

            if (array_key_exists('id', $entry)) {
                $id = (int)$entry['id'];
            } elseif (array_key_exists(0, $entry)) {
                $id = (int)$entry[0];
            }

            if (array_key_exists('nombre', $entry)) {
                $nombre = (string)$entry['nombre'];
            } elseif (array_key_exists(1, $entry)) {
                $nombre = (string)$entry[1];
            }

            if ($id === null) {
                continue;
            }

            $items[] = [
                'id'     => $id,
                'nombre' => $nombre !== null ? $nombre : '',
            ];
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildPersistenceParams(array $data): array
    {
        return [
            ':cooperativa'        => [$data['id_cooperativa'] ?? null, PDO::PARAM_INT],
            ':servicio'           => [$data['id_servicio'] ?? null, PDO::PARAM_INT],
            ':fecha_contratacion' => [$this->normalizeDate($data['fecha_contratacion'] ?? null), $this->paramType($data['fecha_contratacion'] ?? null)],
            ':fecha_caducidad'    => [$this->normalizeDate($data['fecha_caducidad'] ?? null), $this->paramType($data['fecha_caducidad'] ?? null)],
            ':fecha_desvinculacion'=> [$this->normalizeDate($data['fecha_desvinculacion'] ?? null), $this->paramType($data['fecha_desvinculacion'] ?? null)],
            ':fecha_finalizacion' => [$this->normalizeDate($data['fecha_finalizacion'] ?? null), $this->paramType($data['fecha_finalizacion'] ?? null)],
            ':periodo'            => [$data['periodo_facturacion'] ?? null, $this->paramType($data['periodo_facturacion'] ?? null)],
            ':tipo_contrato'      => [$data['tipo_contrato'] ?? null, $this->paramType($data['tipo_contrato'] ?? null)],
            ':terminacion_contrato' => [$data['terminacion_contrato'] ?? null, $this->paramType($data['terminacion_contrato'] ?? null)],
            ':estado'             => [$data['estado_pago'] ?? null, $this->paramType($data['estado_pago'] ?? null)],
            ':activo'             => [$this->toBool($data['activo'] ?? true), PDO::PARAM_BOOL],
            ':valor_base'         => [$this->toNumeric($data['valor_base'] ?? $data['valor_contratado'] ?? 0), PDO::PARAM_STR],
            ':valor_individual'   => [$this->toNumeric($data['valor_individual'] ?? null), $this->paramType($data['valor_individual'] ?? null)],
            ':valor_grupal'       => [$this->toNumeric($data['valor_grupal'] ?? null), $this->paramType($data['valor_grupal'] ?? null)],
            ':valor_iva'          => [$this->toNumeric($data['valor_iva'] ?? null), $this->paramType($data['valor_iva'] ?? null)],
            ':valor_total'        => [$this->toNumeric($data['valor_total'] ?? null), $this->paramType($data['valor_total'] ?? null)],
            ':licencias'          => [$data['numero_licencias'] ?? null, $this->paramType($data['numero_licencias'] ?? null, true)],
            ':fecha_ultimo_pago'  => [$this->normalizeDate($data['fecha_ultimo_pago'] ?? null), $this->paramType($data['fecha_ultimo_pago'] ?? null)],
            ':documento'          => [$data['documento_contable'] ?? null, $this->paramType($data['documento_contable'] ?? null)],
            ':observaciones'      => [$data['observaciones'] ?? null, $this->paramType($data['observaciones'] ?? null)],
            ':codigo_red'         => [$data['codigo_red'] ?? null, $this->paramType($data['codigo_red'] ?? null)],
        ];
    }

    /**
     * @param array<int,int> $servicios
     */
    private function syncServicios(int $contratoId, array $servicios): void
    {
        $servicios = array_values(array_unique(array_filter(array_map('intval', $servicios), static fn($id) => $id > 0)));

        $this->db->execute(
            'DELETE FROM ' . self::PIVOT_TABLE . ' WHERE id_contratacion = :contrato',
            [':contrato' => [$contratoId, PDO::PARAM_INT]]
        );

        if (empty($servicios)) {
            return;
        }

        $sql = 'INSERT INTO ' . self::PIVOT_TABLE . ' (id_contratacion, id_servicio) VALUES (:contrato, :servicio)';
        foreach ($servicios as $servicioId) {
            $this->db->execute($sql, [
                ':contrato' => [$contratoId, PDO::PARAM_INT],
                ':servicio' => [$servicioId, PDO::PARAM_INT],
            ]);
        }
    }

    private function paramType($value, bool $forceInt = false): int
    {
        if ($forceInt) {
            return $value === null || $value === '' ? PDO::PARAM_NULL : PDO::PARAM_INT;
        }

        if ($value === null || $value === '') {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    private function toNumeric($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return number_format((float)$value, 2, '.', '');
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }
        return (bool)$value;
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $str = trim((string)$value);
        if ($str === '') {
            return null;
        }
        $dt = date_create($str);
        return $dt ? $dt->format('Y-m-d') : null;
    }
}
