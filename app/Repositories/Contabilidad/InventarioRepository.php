<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;
use Throwable;

final class InventarioRepository extends BaseRepository
{
    private const TABLE = 'public.contabilidad_inventario_equipos';

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function list(array $filters = []): array
    {
        $params = [];
        $where  = $this->buildFilters($filters, $params);

        $sql = 'SELECT id, nombre, descripcion, codigo, estado, responsable, responsable_contacto, fecha_entrega,'
            . ' documento_path, comentarios, created_at, updated_at, serie, marca, modelo'
            . ' FROM ' . self::TABLE
            . $where
            . ' ORDER BY fecha_entrega DESC, id DESC';

        try {
            return $this->db->fetchAll($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el inventario de equipos.', 0, $e);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                nombre,
                descripcion,
                codigo,
                estado,
                responsable,
                responsable_contacto,
                fecha_entrega,
                documento_path,
                comentarios,
                serie,
                marca,
                modelo,
                created_at
            ) VALUES (
                :nombre,
                :descripcion,
                :codigo,
                :estado,
                :responsable,
                :responsable_contacto,
                :fecha_entrega,
                :documento_path,
                :comentarios,
                :serie,
                :marca,
                :modelo,
                NOW()
            ) RETURNING id';

        $params = [
            ':nombre'               => [$data['nombre'], PDO::PARAM_STR],
            ':descripcion'          => [$data['descripcion'] ?? null, $this->nullableStr($data['descripcion'] ?? null)],
            ':codigo'               => [$data['codigo'], PDO::PARAM_STR],
            ':estado'               => [$data['estado'], PDO::PARAM_STR],
            ':responsable'          => [$data['responsable'], PDO::PARAM_STR],
            ':responsable_contacto' => [$data['responsable_contacto'] ?? null, $this->nullableStr($data['responsable_contacto'] ?? null)],
            ':fecha_entrega'        => [$data['fecha_entrega'], PDO::PARAM_STR],
            ':documento_path'       => [$data['documento_path'] ?? null, $this->nullableStr($data['documento_path'] ?? null)],
            ':comentarios'          => [$data['comentarios'] ?? null, $this->nullableStr($data['comentarios'] ?? null)],
            ':serie'                => [$data['serie'] ?? null, $this->nullableStr($data['serie'] ?? null)],
            ':marca'                => [$data['marca'] ?? null, $this->nullableStr($data['marca'] ?? null)],
            ':modelo'               => [$data['modelo'] ?? null, $this->nullableStr($data['modelo'] ?? null)],
        ];

        try {
            $rows = $this->db->execute($sql, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo registrar el equipo.', 0, $e);
        }

        $row = is_array($rows) && isset($rows[0]['id']) ? $rows[0] : null;
        return $row ? (int)$row['id'] : 0;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        $sql = 'SELECT id, nombre, descripcion, codigo, estado, responsable, responsable_contacto, fecha_entrega,'
            . ' documento_path, comentarios, created_at, updated_at, serie, marca, modelo'
            . ' FROM ' . self::TABLE
            . ' WHERE id = :id';

        try {
            $row = $this->db->fetch($sql, [':id' => [$id, PDO::PARAM_INT]]);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo obtener el equipo solicitado.', 0, $e);
        }

        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $filters
     * @return int
     */
    public function count(array $filters = []): int
    {
        $params = [];
        $where  = $this->buildFilters($filters, $params);

        try {
            $row = $this->db->fetch('SELECT COUNT(*) AS total FROM ' . self::TABLE . $where, $params);
        } catch (Throwable $e) {
            throw new RuntimeException('No se pudo contar el inventario.', 0, $e);
        }

        return $row ? (int)$row['total'] : 0;
    }

    private function nullableStr($value): int
    {
        return ($value === null || $value === '') ? PDO::PARAM_NULL : PDO::PARAM_STR;
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,array{0:mixed,1:int}> $params
     */
    private function buildFilters(array $filters, array &$params): string
    {
        $conditions = [];

        $estado = isset($filters['estado']) ? trim((string)$filters['estado']) : '';
        if ($estado !== '') {
            $conditions[] = 'estado = :estado';
            $params[':estado'] = [$estado, PDO::PARAM_STR];
        }

        $search = isset($filters['q']) ? trim((string)$filters['q']) : '';
        if ($search !== '') {
            $conditions[] = '(nombre ILIKE :q OR codigo ILIKE :q)';
            $params[':q'] = ['%' . $search . '%', PDO::PARAM_STR];
        }

        $responsable = isset($filters['responsable']) ? trim((string)$filters['responsable']) : '';
        if ($responsable !== '') {
            $conditions[] = 'responsable ILIKE :responsable';
            $params[':responsable'] = ['%' . $responsable . '%', PDO::PARAM_STR];
        }

        $desde = isset($filters['desde']) ? trim((string)$filters['desde']) : '';
        if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $conditions[] = 'fecha_entrega >= :desde';
            $params[':desde'] = [$desde, PDO::PARAM_STR];
        }

        $hasta = isset($filters['hasta']) ? trim((string)$filters['hasta']) : '';
        if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $conditions[] = 'fecha_entrega <= :hasta';
            $params[':hasta'] = [$hasta, PDO::PARAM_STR];
        }

        return $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    }
}
