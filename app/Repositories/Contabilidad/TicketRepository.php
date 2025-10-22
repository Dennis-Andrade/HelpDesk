<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;
use Throwable;

final class TicketRepository extends BaseRepository
{
    private const TABLE       = 'public.contabilidad_tickets';
    private const COOPS_TABLE = 'public.cooperativas';

    /**
     * @param array<string,mixed> $filters
     * @return array{items:array<int,array<string,mixed>>,total:int,page:int,perPage:int}
     */
    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(5, min(60, $perPage));
        $offset  = ($page - 1) * $perPage;

        $bindings = [];
        $where = $this->buildFilters($filters, $bindings);

        $countSql = 'SELECT COUNT(*) AS total FROM ' . self::TABLE . ' t'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = t.id_cooperativa'
            . ($where !== '' ? ' ' . $where : '');

        try {
            $countRow = $this->db->fetch($countSql, $bindings);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo contar los tickets contables.', 0, $e);
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

        $bindings[':limit']  = [$perPage, PDO::PARAM_INT];
        $bindings[':offset'] = [$offset, PDO::PARAM_INT];

        $sql = 'SELECT t.id, t.codigo, t.id_cooperativa, c.nombre AS cooperativa, t.asunto, t.categoria,'
            . ' t.prioridad, t.estado, t.descripcion, t.observaciones, t.fecha_apertura, t.fecha_cierre'
            . ' FROM ' . self::TABLE . ' t'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = t.id_cooperativa'
            . ($where !== '' ? ' ' . $where : '')
            . ' ORDER BY t.fecha_apertura DESC, t.id DESC'
            . ' LIMIT :limit OFFSET :offset';

        try {
            $items = $this->db->fetchAll($sql, $bindings);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el listado de tickets contables.', 0, $e);
        }

        return [
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        ];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                codigo,
                id_cooperativa,
                id_contratacion,
                asunto,
                categoria,
                prioridad,
                estado,
                descripcion,
                observaciones,
                creado_por,
                fecha_apertura
            ) VALUES (
                :codigo,
                :id_cooperativa,
                :id_contratacion,
                :asunto,
                :categoria,
                :prioridad,
                :estado,
                :descripcion,
                :observaciones,
                :creado_por,
                NOW()
            ) RETURNING id';

        $codigo = $this->generateCodigo();
        $params = $this->buildParams($data);
        $params[':codigo'] = [$codigo, PDO::PARAM_STR];

        try {
            $rows = $this->db->execute($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo registrar el ticket contable.', 0, $e);
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
                id_cooperativa = :id_cooperativa,
                id_contratacion = :id_contratacion,
                asunto = :asunto,
                categoria = :categoria,
                prioridad = :prioridad,
                estado = :estado,
                descripcion = :descripcion,
                observaciones = :observaciones,
                actualizado_por = :creado_por,
                actualizado_en = NOW()
            WHERE id = :id';

        $params = $this->buildParams($data);
        $params[':id'] = [$id, PDO::PARAM_INT];

        try {
            $this->db->execute($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo actualizar el ticket.', 0, $e);
        }
    }

    public function delete(int $id): void
    {
        try {
            $this->db->execute('DELETE FROM ' . self::TABLE . ' WHERE id = :id', [':id' => [$id, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo eliminar el ticket.', 0, $e);
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        $sql = 'SELECT t.*, c.nombre AS cooperativa'
            . ' FROM ' . self::TABLE . ' t'
            . ' INNER JOIN ' . self::COOPS_TABLE . ' c ON c.id_cooperativa = t.id_cooperativa'
            . ' WHERE t.id = :id';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el ticket solicitado.', 0, $e);
        }

        return $row ?: null;
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
    public function catalogoPrioridades(): array
    {
        return ['Baja', 'Media', 'Alta', 'Crítica'];
    }

    /**
     * @return array<int,string>
     */
    public function catalogoEstados(): array
    {
        return ['Nuevo', 'En gestión', 'Resuelto', 'Cancelado'];
    }

    /**
     * @return array<int,string>
     */
    public function catalogoCategorias(): array
    {
        return ['Facturación', 'Cobranza', 'Retenciones', 'Declaraciones', 'Soporte'];
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,array{0:mixed,1:int}> $bindings
     */
    private function buildFilters(array $filters, array &$bindings): string
    {
        $conditions = [];
        if (!empty($filters['estado'])) {
            $conditions[] = 't.estado = :estado';
            $bindings[':estado'] = [$filters['estado'], PDO::PARAM_STR];
        }
        if (!empty($filters['prioridad'])) {
            $conditions[] = 't.prioridad = :prioridad';
            $bindings[':prioridad'] = [$filters['prioridad'], PDO::PARAM_STR];
        }
        if (!empty($filters['categoria'])) {
            $conditions[] = 't.categoria = :categoria';
            $bindings[':categoria'] = [$filters['categoria'], PDO::PARAM_STR];
        }
        if (!empty($filters['coop'])) {
            $conditions[] = 't.id_cooperativa = :coop';
            $bindings[':coop'] = [(int)$filters['coop'], PDO::PARAM_INT];
        }
        if (!empty($filters['q'])) {
            $conditions[] = '(t.codigo ILIKE :q OR t.asunto ILIKE :q OR t.descripcion ILIKE :q)';
            $bindings[':q'] = ['%' . $filters['q'] . '%', PDO::PARAM_STR];
        }
        if (empty($conditions)) {
            return '';
        }
        return ' WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,array{0:mixed,1:int}>
     */
    private function buildParams(array $data): array
    {
        return [
            ':id_cooperativa'  => [$data['id_cooperativa'], PDO::PARAM_INT],
            ':id_contratacion' => [$data['id_contratacion'] ?? null, $this->nullableInt($data['id_contratacion'] ?? null)],
            ':asunto'          => [$data['asunto'], PDO::PARAM_STR],
            ':categoria'       => [$data['categoria'], PDO::PARAM_STR],
            ':prioridad'       => [$data['prioridad'], PDO::PARAM_STR],
            ':estado'          => [$data['estado'], PDO::PARAM_STR],
            ':descripcion'     => [$data['descripcion'], PDO::PARAM_STR],
            ':observaciones'   => [$data['observaciones'] ?? null, $this->nullableStr($data['observaciones'] ?? null)],
            ':creado_por'      => [$data['creado_por'] ?? null, $this->nullableInt($data['creado_por'] ?? null)],
        ];
    }

    private function generateCodigo(): string
    {
        $year = date('Y');
        $sql = 'SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM ' . self::TABLE
            . ' WHERE EXTRACT(YEAR FROM fecha_apertura) = :year';

        try {
            $row = $this->db->fetch($sql, [':year' => [$year, PDO::PARAM_STR]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo generar el código del ticket.', 0, $e);
        }

        $next = $row ? (int)$row['next_id'] : 1;
        return sprintf('CTK-%s-%05d', $year, $next);
    }

    private function nullableInt($value): int
    {
        return ($value === null || $value === '' || (int)$value <= 0) ? PDO::PARAM_NULL : PDO::PARAM_INT;
    }

    private function nullableStr($value): int
    {
        return ($value === null || $value === '') ? PDO::PARAM_NULL : PDO::PARAM_STR;
    }
}
