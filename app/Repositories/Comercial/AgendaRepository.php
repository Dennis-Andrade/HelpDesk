<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use RuntimeException;

final class AgendaRepository extends BaseRepository
{
    private const TABLE = 'public.agenda_contactos';
    private const TABLE_COOPS = 'public.cooperativas';

    /**
     * @return array<int,array{id:int,nombre:string}>
     */
    public function obtenerCooperativas(): array
    {
        $sql = 'SELECT id_cooperativa AS id, nombre FROM ' . self::TABLE_COOPS . ' ORDER BY nombre ASC';
        try {
            $rows = $this->db->fetchAll($sql);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudieron obtener las entidades.', 0, $e);
        }

        $list = [];
        foreach ($rows as $row) {
            if (!isset($row['id'], $row['nombre'])) {
                continue;
            }
            $list[] = [
                'id' => (int)$row['id'],
                'nombre' => (string)$row['nombre'],
            ];
        }

        return $list;
    }

    /**
     * @param array{q?:string,coop?:int|null,estado?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function listar(array $filters): array
    {
        return $this->consultar($filters, false);
    }

    /**
     * @param array{q?:string,coop?:int|null,estado?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    public function listarParaExportar(array $filters): array
    {
        return $this->consultar($filters, true);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function crear(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE . ' (
                id_cooperativa,
                titulo,
                fecha_evento,
                contacto,
                telefono_contacto,
                oficial_nombre,
                oficial_correo,
                cargo,
                nota,
                estado,
                creado_por
            ) VALUES (
                :id_cooperativa,
                :titulo,
                :fecha_evento,
                :contacto,
                :telefono_contacto,
                :oficial_nombre,
                :oficial_correo,
                :cargo,
                :nota,
                :estado,
                :creado_por
            ) RETURNING id_evento AS id';

        $params = [
            ':id_cooperativa'   => $data['id_cooperativa'] === null
                ? [null, \PDO::PARAM_NULL]
                : [(int)$data['id_cooperativa'], \PDO::PARAM_INT],
            ':titulo'           => [(string)$data['titulo'], \PDO::PARAM_STR],
            ':fecha_evento'     => [(string)$data['fecha_evento'], \PDO::PARAM_STR],
            ':contacto'         => $this->nullableStringParam($data['contacto'] ?? null),
            ':telefono_contacto'=> $this->nullableStringParam($data['telefono_contacto'] ?? null),
            ':oficial_nombre'   => $this->nullableStringParam($data['oficial_nombre'] ?? null),
            ':oficial_correo'   => $this->nullableStringParam($data['oficial_correo'] ?? null),
            ':cargo'            => $this->nullableStringParam($data['cargo'] ?? null),
            ':nota'             => $this->nullableStringParam($data['nota'] ?? null),
            ':estado'           => [(string)$data['estado'], \PDO::PARAM_STR],
            ':creado_por'       => $data['creado_por'] === null
                ? [null, \PDO::PARAM_NULL]
                : [(int)$data['creado_por'], \PDO::PARAM_INT],
        ];

        try {
            $result = $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo registrar el contacto.', 0, $e);
        }

        $row = is_array($result) && isset($result[0]) ? $result[0] : null;
        return $row && isset($row['id']) ? (int)$row['id'] : 0;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function actualizar(int $id, array $data): void
    {
        $sql = 'UPDATE ' . self::TABLE . ' SET
                id_cooperativa = :id_cooperativa,
                titulo = :titulo,
                fecha_evento = :fecha_evento,
                contacto = :contacto,
                telefono_contacto = :telefono_contacto,
                oficial_nombre = :oficial_nombre,
                oficial_correo = :oficial_correo,
                cargo = :cargo,
                nota = :nota,
                estado = :estado,
                updated_at = NOW()
            WHERE id_evento = :id';

        $params = [
            ':id_cooperativa'   => $data['id_cooperativa'] === null
                ? [null, \PDO::PARAM_NULL]
                : [(int)$data['id_cooperativa'], \PDO::PARAM_INT],
            ':titulo'           => [(string)$data['titulo'], \PDO::PARAM_STR],
            ':fecha_evento'     => [(string)$data['fecha_evento'], \PDO::PARAM_STR],
            ':contacto'         => $this->nullableStringParam($data['contacto'] ?? null),
            ':telefono_contacto'=> $this->nullableStringParam($data['telefono_contacto'] ?? null),
            ':oficial_nombre'   => $this->nullableStringParam($data['oficial_nombre'] ?? null),
            ':oficial_correo'   => $this->nullableStringParam($data['oficial_correo'] ?? null),
            ':cargo'            => $this->nullableStringParam($data['cargo'] ?? null),
            ':nota'             => $this->nullableStringParam($data['nota'] ?? null),
            ':estado'           => [(string)$data['estado'], \PDO::PARAM_STR],
            ':id'               => [$id, \PDO::PARAM_INT],
        ];

        try {
            $this->db->execute($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo actualizar el contacto.', 0, $e);
        }
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $sql = 'UPDATE ' . self::TABLE
            . ' SET estado = :estado, updated_at = NOW()'
            . ' WHERE id_evento = :id';

        try {
            $this->db->execute($sql, [
                ':estado' => [$estado, \PDO::PARAM_STR],
                ':id'     => [$id, \PDO::PARAM_INT],
            ]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo actualizar el estado.', 0, $e);
        }
    }

    public function eliminar(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE id_evento = :id';
        try {
            $this->db->execute($sql, [':id' => [$id, \PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo eliminar el contacto.', 0, $e);
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        $sql = $this->baseSelect() . ' WHERE a.id_evento = :id LIMIT 1';
        try {
            $row = $this->db->fetch($sql, [':id' => [$id, \PDO::PARAM_INT]]);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo consultar el contacto.', 0, $e);
        }
        if (!$row) {
            return null;
        }
        return $this->mapRow($row);
    }

    /**
     * @param array{q?:string,coop?:int|null,estado?:string} $filters
     * @return array<int,array<string,mixed>>
     */
    private function consultar(array $filters, bool $todos): array
    {
        $sql = $this->baseSelect();
        $conditions = [];
        $params = [];

        $texto = trim((string)($filters['q'] ?? ''));
        if ($texto !== '') {
            $conditions[] = '(
                a.titulo ILIKE :texto
                OR a.nota ILIKE :texto
                OR a.oficial_nombre ILIKE :texto
                OR a.oficial_correo ILIKE :texto
                OR a.cargo ILIKE :texto
                OR c.nombre ILIKE :texto
            )';
            $params[':texto'] = ['%' . $texto . '%', \PDO::PARAM_STR];
        }

        $coop = $filters['coop'] ?? null;
        if ($coop !== null && (int)$coop > 0) {
            $conditions[] = 'a.id_cooperativa = :coop';
            $params[':coop'] = [(int)$coop, \PDO::PARAM_INT];
        }

        $estado = trim((string)($filters['estado'] ?? ''));
        if ($estado !== '') {
            $conditions[] = 'a.estado = :estado';
            $params[':estado'] = [$estado, \PDO::PARAM_STR];
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY a.fecha_evento ASC, a.created_at DESC';

        if (!$todos) {
            $sql .= ' LIMIT 200';
        }

        try {
            $rows = $this->db->fetchAll($sql, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('No se pudo obtener la agenda de contactos.', 0, $e);
        }

        $data = [];
        foreach ($rows as $row) {
            $data[] = $this->mapRow($row);
        }

        return $data;
    }

    private function baseSelect(): string
    {
        return 'SELECT
                a.id_evento AS id,
                a.id_cooperativa,
                a.titulo,
                a.fecha_evento,
                a.nota,
                a.contacto,
                a.telefono_contacto,
                a.oficial_nombre,
                a.oficial_correo,
                a.cargo,
                a.estado,
                a.created_at,
                a.updated_at,
                c.nombre AS coop_nombre,
                c.telefono AS coop_telefono,
                c.email AS coop_email,
                c.provincia AS coop_provincia,
                c.canton AS coop_canton
            FROM ' . self::TABLE . ' a
            LEFT JOIN ' . self::TABLE_COOPS . ' c ON c.id_cooperativa = a.id_cooperativa';
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int)$row['id'] : 0,
            'id_cooperativa' => array_key_exists('id_cooperativa', $row) && $row['id_cooperativa'] !== null ? (int)$row['id_cooperativa'] : null,
            'titulo' => (string)($row['titulo'] ?? ''),
            'fecha_evento' => (string)($row['fecha_evento'] ?? ''),
            'nota' => array_key_exists('nota', $row) && $row['nota'] !== null ? (string)$row['nota'] : null,
            'contacto' => array_key_exists('contacto', $row) && $row['contacto'] !== null ? (string)$row['contacto'] : null,
            'telefono_contacto' => array_key_exists('telefono_contacto', $row) && $row['telefono_contacto'] !== null ? (string)$row['telefono_contacto'] : null,
            'oficial_nombre' => array_key_exists('oficial_nombre', $row) && $row['oficial_nombre'] !== null ? (string)$row['oficial_nombre'] : null,
            'oficial_correo' => array_key_exists('oficial_correo', $row) && $row['oficial_correo'] !== null ? (string)$row['oficial_correo'] : null,
            'cargo' => array_key_exists('cargo', $row) && $row['cargo'] !== null ? (string)$row['cargo'] : null,
            'estado' => (string)($row['estado'] ?? ''),
            'coop_nombre' => array_key_exists('coop_nombre', $row) && $row['coop_nombre'] !== null ? (string)$row['coop_nombre'] : null,
            'coop_telefono' => array_key_exists('coop_telefono', $row) && $row['coop_telefono'] !== null ? (string)$row['coop_telefono'] : null,
            'coop_email' => array_key_exists('coop_email', $row) && $row['coop_email'] !== null ? (string)$row['coop_email'] : null,
            'coop_provincia' => array_key_exists('coop_provincia', $row) && $row['coop_provincia'] !== null ? (string)$row['coop_provincia'] : null,
            'coop_canton' => array_key_exists('coop_canton', $row) && $row['coop_canton'] !== null ? (string)$row['coop_canton'] : null,
        ];
    }
}
