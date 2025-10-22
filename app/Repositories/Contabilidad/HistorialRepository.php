<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

final class HistorialRepository extends BaseRepository
{
    private const TABLE = 'public.contabilidad_facturacion_historial';
    private const COOP_TABLE = 'public.cooperativas';
    private const CONTRATO_TABLE = 'public.contrataciones_servicios';

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
        $where = $this->buildFilters($filters, $params);

        $joins = ' LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = h.id_cooperativa'
                . ' LEFT JOIN ' . self::CONTRATO_TABLE . ' cs ON cs.id_contratacion = h.id_contratacion';

        $countSql = 'SELECT COUNT(*) AS total FROM ' . self::TABLE . ' h' . $joins . $where;

        try {
            $row = $this->db->fetch($countSql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al contar el historial de facturación.', 0, $e);
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

        $sql = 'SELECT h.id,
                       h.id_cooperativa,
                       coop.nombre AS cooperativa,
                       h.id_contratacion,
                       cs.id_servicio,
                       h.periodo,
                       h.fecha_emision,
                       h.fecha_vencimiento,
                       h.fecha_pago,
                       h.monto_base,
                       h.monto_iva,
                       h.monto_total,
                       h.estado,
                       h.comprobante_path,
                       h.observaciones,
                       h.creado_en,
                       h.actualizado_en
                  FROM ' . self::TABLE . ' h' . $joins . $where . '
             ORDER BY h.fecha_emision DESC, h.id DESC
             LIMIT :limit OFFSET :offset';

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el historial de facturación.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapHistorial($row);
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

        $sql = 'SELECT h.*, coop.nombre AS cooperativa
                  FROM ' . self::TABLE . ' h
             LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = h.id_cooperativa
                 WHERE h.id = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el registro del historial.', 0, $e);
        }

        return $row ? $this->mapHistorial($row) : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listByContrato(int $contratoId): array
    {
        $sql = 'SELECT h.*, coop.nombre AS cooperativa
                  FROM ' . self::TABLE . ' h
             LEFT JOIN ' . self::COOP_TABLE . ' coop ON coop.id_cooperativa = h.id_cooperativa
                 WHERE h.id_contratacion = :contrato
                 ORDER BY h.fecha_emision DESC, h.id DESC';

        try {
            $rows = $this->db->fetchAll($sql, [':contrato' => [$contratoId, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener el historial del contrato.', 0, $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapHistorial($row);
        }

        return $items;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                    id_cooperativa,
                    id_contratacion,
                    periodo,
                    fecha_emision,
                    fecha_vencimiento,
                    fecha_pago,
                    monto_base,
                    monto_iva,
                    monto_total,
                    estado,
                    comprobante_path,
                    observaciones
                ) VALUES (
                    :cooperativa,
                    :contrato,
                    :periodo,
                    :fecha_emision,
                    :fecha_vencimiento,
                    :fecha_pago,
                    :monto_base,
                    :monto_iva,
                    :monto_total,
                    :estado,
                    :comprobante,
                    :observaciones
                ) RETURNING id';

        $params = $this->buildParams($data);

        try {
            $rows = $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo registrar el pago.', 0, $e);
        }

        $row = is_array($rows) && isset($rows[0]['id']) ? $rows[0] : null;
        return $row ? (int)$row['id'] : 0;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET
                    id_cooperativa = :cooperativa,
                    id_contratacion = :contrato,
                    periodo = :periodo,
                    fecha_emision = :fecha_emision,
                    fecha_vencimiento = :fecha_vencimiento,
                    fecha_pago = :fecha_pago,
                    monto_base = :monto_base,
                    monto_iva = :monto_iva,
                    monto_total = :monto_total,
                    estado = :estado,
                    comprobante_path = :comprobante,
                    observaciones = :observaciones,
                    actualizado_en = NOW()
                WHERE id = :id';

        $params = $this->buildParams($data);
        $params[':id'] = [$id, PDO::PARAM_INT];

        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo actualizar el registro del historial.', 0, $e);
        }
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = :id';

        try {
            $this->db->execute($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo eliminar el registro del historial.', 0, $e);
        }
    }

    /**
     * @return array<int,string>
     */
    public function estados(): array
    {
        return ['pendiente', 'pagado', 'vencido', 'parcial', 'anulado'];
    }

    private function buildFilters(array $filters, array &$params): string
    {
        $conditions = [];

        $q = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($q !== '') {
            $conditions[] = '(
                unaccent(lower(coop.nombre)) LIKE unaccent(lower(:q_like))
                OR unaccent(lower(h.observaciones)) LIKE unaccent(lower(:q_like))
            )';
            $params[':q_like'] = ['%' . $q . '%', PDO::PARAM_STR];
        }

        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $conditions[] = 'LOWER(h.estado) = LOWER(:estado)';
            $params[':estado'] = [$filters['estado'], PDO::PARAM_STR];
        }

        if (isset($filters['cooperativa']) && (int)$filters['cooperativa'] > 0) {
            $conditions[] = 'h.id_cooperativa = :cooperativa';
            $params[':cooperativa'] = [(int)$filters['cooperativa'], PDO::PARAM_INT];
        }

        if (isset($filters['desde']) && $filters['desde'] !== '') {
            $conditions[] = 'h.fecha_emision >= :desde';
            $params[':desde'] = [$filters['desde'], PDO::PARAM_STR];
        }

        if (isset($filters['hasta']) && $filters['hasta'] !== '') {
            $conditions[] = 'h.fecha_emision <= :hasta';
            $params[':hasta'] = [$filters['hasta'], PDO::PARAM_STR];
        }

        if (empty($conditions)) {
            return '';
        }

        return ' WHERE ' . implode(' AND ', $conditions);
    }

    private function mapHistorial(array $row): array
    {
        return [
            'id'                => (int)($row['id'] ?? 0),
            'id_cooperativa'    => (int)($row['id_cooperativa'] ?? 0),
            'cooperativa'       => (string)($row['cooperativa'] ?? ''),
            'id_contratacion'   => isset($row['id_contratacion']) ? (int)$row['id_contratacion'] : null,
            'periodo'           => (string)($row['periodo'] ?? ''),
            'fecha_emision'     => $this->normalizeDate($row['fecha_emision'] ?? null),
            'fecha_vencimiento' => $this->normalizeDate($row['fecha_vencimiento'] ?? null),
            'fecha_pago'        => $this->normalizeDate($row['fecha_pago'] ?? null),
            'monto_base'        => $this->toFloat($row['monto_base'] ?? 0),
            'monto_iva'         => $this->toFloat($row['monto_iva'] ?? 0),
            'monto_total'       => $this->toFloat($row['monto_total'] ?? 0),
            'estado'            => (string)($row['estado'] ?? ''),
            'comprobante_path'  => (string)($row['comprobante_path'] ?? ''),
            'observaciones'     => (string)($row['observaciones'] ?? ''),
            'creado_en'         => isset($row['creado_en']) ? (string)$row['creado_en'] : null,
            'actualizado_en'    => isset($row['actualizado_en']) ? (string)$row['actualizado_en'] : null,
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildParams(array $data): array
    {
        return [
            ':cooperativa'  => [$data['id_cooperativa'] ?? null, PDO::PARAM_INT],
            ':contrato'     => [$data['id_contratacion'] ?? null, $this->paramType($data['id_contratacion'] ?? null, true)],
            ':periodo'      => [$data['periodo'] ?? null, $this->paramType($data['periodo'] ?? null)],
            ':fecha_emision'=> [$this->normalizeDate($data['fecha_emision'] ?? null), $this->paramType($data['fecha_emision'] ?? null)],
            ':fecha_vencimiento' => [$this->normalizeDate($data['fecha_vencimiento'] ?? null), $this->paramType($data['fecha_vencimiento'] ?? null)],
            ':fecha_pago'   => [$this->normalizeDate($data['fecha_pago'] ?? null), $this->paramType($data['fecha_pago'] ?? null)],
            ':monto_base'   => [$this->toNumeric($data['monto_base'] ?? 0), PDO::PARAM_STR],
            ':monto_iva'    => [$this->toNumeric($data['monto_iva'] ?? 0), PDO::PARAM_STR],
            ':monto_total'  => [$this->toNumeric($data['monto_total'] ?? 0), PDO::PARAM_STR],
            ':estado'       => [$data['estado'] ?? null, $this->paramType($data['estado'] ?? null)],
            ':comprobante'  => [$data['comprobante_path'] ?? null, $this->paramType($data['comprobante_path'] ?? null)],
            ':observaciones'=> [$data['observaciones'] ?? null, $this->paramType($data['observaciones'] ?? null)],
        ];
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

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $dt = date_create((string)$value);
        return $dt ? $dt->format('Y-m-d') : null;
    }

    private function toNumeric($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }

    private function toFloat($value): float
    {
        return (float)$value;
    }
}
