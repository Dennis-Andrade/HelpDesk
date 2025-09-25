<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use RuntimeException;

final class EntidadRepository extends BaseRepository
{
    private const TABLE = 'public.cooperativas';
    private const VIEW_CARDS = 'public.v_cooperativas_cards';

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, perPage:int, page:int}
     */
    public function search(?string $q, int $perPage = 12, int $page = 1): array
    {
        $perPage = $perPage > 0 ? $perPage : 12;
        $page    = $page > 0 ? $page : 1;
        $offset  = ($page - 1) * $perPage;

        $term = $q !== null ? trim($q) : null;
        if ($term === '') {
            $term = null;
        }

        $sql = 'SELECT id, nombre, ruc, telefono, email, provincia, canton, segmento, servicios_text, total
                FROM public.f_cooperativas_cards(:q, :limit, :offset)';

        $params = array(
            ':q'      => $term === null ? array(null, PDO::PARAM_NULL) : array($term, PDO::PARAM_STR),
            ':limit'  => array($perPage, PDO::PARAM_INT),
            ':offset' => array($offset >= 0 ? $offset : 0, PDO::PARAM_INT),
        );

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener el listado de entidades.', 0, $e);
        }

        $total = 0;
        if (!empty($rows)) {
            $total = (int)($rows[0]['total'] ?? 0);
        }

        return array(
            'items'   => $rows,
            'total'   => $total,
            'perPage' => $perPage,
            'page'    => $page,
        );
    }

    public function findCardById(int $id): ?array
    {
        $sql = 'SELECT id, nombre, ruc, telefono, email, provincia, canton, segmento, servicios_text
                FROM ' . self::VIEW_CARDS . ' WHERE id = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener la entidad solicitada.', 0, $e);
        }

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT id, nombre, ruc, provincia_id, canton_id, email, segmento
                FROM ' . self::TABLE . ' WHERE id = :id LIMIT 1';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($id, PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener la entidad para edición.', 0, $e);
        }

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (nombre, ruc, provincia_id, canton_id, email, segmento)
                VALUES (:nombre, :ruc, :provincia_id, :canton_id, :email, :segmento)
                RETURNING id';

        $params = array(
            ':nombre'       => array((string)($data['nombre'] ?? ''), PDO::PARAM_STR),
            ':ruc'          => $this->bindNullableString($data['ruc'] ?? null),
            ':provincia_id' => $this->bindNullableInt($data['provincia_id'] ?? null),
            ':canton_id'    => $this->bindNullableInt($data['canton_id'] ?? null),
            ':email'        => $this->bindNullableString($data['email'] ?? null),
            ':segmento'     => $this->bindNullableString($data['segmento'] ?? null),
        );

        $this->db->begin();
        try {
            $rows = $this->db->execute($sql, $params);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo registrar la entidad.', 0, $e);
        }

        if (!is_array($rows) || !isset($rows[0]['id'])) {
            throw new RuntimeException('INSERT cooperativas no devolvió id');
        }

        return (int)$rows[0]['id'];
    }

    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET
                    nombre = :nombre,
                    ruc = :ruc,
                    provincia_id = :provincia_id,
                    canton_id = :canton_id,
                    email = :email,
                    segmento = :segmento
                WHERE id = :id';

        $params = array(
            ':id'           => array($id, PDO::PARAM_INT),
            ':nombre'       => array((string)($data['nombre'] ?? ''), PDO::PARAM_STR),
            ':ruc'          => $this->bindNullableString($data['ruc'] ?? null),
            ':provincia_id' => $this->bindNullableInt($data['provincia_id'] ?? null),
            ':canton_id'    => $this->bindNullableInt($data['canton_id'] ?? null),
            ':email'        => $this->bindNullableString($data['email'] ?? null),
            ':segmento'     => $this->bindNullableString($data['segmento'] ?? null),
        );

        $this->db->begin();
        try {
            $this->db->execute($sql, $params);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo actualizar la entidad.', 0, $e);
        }
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id = :id';

        $this->db->begin();
        try {
            $this->db->execute($sql, array(':id' => array($id, PDO::PARAM_INT)));
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw new RuntimeException('No se pudo eliminar la entidad.', 0, $e);
        }
    }

    private function bindNullableString($value): array
    {
        if ($value === null) {
            return array(null, PDO::PARAM_NULL);
        }
        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            return array(null, PDO::PARAM_NULL);
        }
        return array($trimmed, PDO::PARAM_STR);
    }

    private function bindNullableInt($value): array
    {
        if ($value === null || $value === '') {
            return array(null, PDO::PARAM_NULL);
        }
        return array((int)$value, PDO::PARAM_INT);
    }
}
