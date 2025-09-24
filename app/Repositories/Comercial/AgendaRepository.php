<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;
use PDOStatement;

final class AgendaRepository extends BaseRepository
{
    private const TABLE            = 'public.agenda_eventos';
    private const TABLE_COOPS      = 'public.cooperativas';
    private const COL_ID           = 'id_evento';
    private const COL_COOP_ID      = 'id_cooperativa';
    private const COL_TITULO       = 'titulo';
    private const COL_DESCRIPCION  = 'descripcion';
    private const COL_FECHA        = 'fecha_evento';
    private const COL_TELF         = 'telefono_contacto';
    private const COL_MAIL         = 'email_contacto';
    private const COL_ESTADO       = 'estado';
    private const COL_CREATED_AT   = 'created_at';
    private const COL_UPDATED_AT   = 'updated_at';
    private const COL_COOP_NOMBRE  = 'nombre';

    /**
     * @param array{texto?:string,desde?:string,hasta?:string,estado?:string} $filters
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,per_page:int}
     */
    public function search(array $filters, int $page, int $perPage): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset  = ($page - 1) * $perPage;

        $conditions = [];
        $params     = [];

        $texto = trim((string)($filters['texto'] ?? ''));
        if ($texto !== '') {
            $conditions[]   = 'c.' . self::COL_COOP_NOMBRE . ' ILIKE :texto';
            $params[':texto'] = '%' . $texto . '%';
        }

        $desde = trim((string)($filters['desde'] ?? ''));
        if ($desde !== '') {
            $conditions[]   = 'ae.' . self::COL_FECHA . ' >= :desde';
            $params[':desde'] = $desde;
        }

        $hasta = trim((string)($filters['hasta'] ?? ''));
        if ($hasta !== '') {
            $conditions[]   = 'ae.' . self::COL_FECHA . ' <= :hasta';
            $params[':hasta'] = $hasta;
        }

        $estado = trim((string)($filters['estado'] ?? ''));
        if ($estado !== '') {
            $conditions[]    = 'ae.' . self::COL_ESTADO . ' = :estado';
            $params[':estado'] = $estado;
        }

        $whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $sqlCount = 'SELECT COUNT(*) FROM ' . self::TABLE . ' ae JOIN ' . self::TABLE_COOPS . ' c ON c.' . self::COL_COOP_ID . ' = ae.' . self::COL_COOP_ID . $whereSql;
        $stCount  = $this->pdo->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stCount->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stCount->execute();
        $total = (int)$stCount->fetchColumn();

        $sql = 'SELECT'
            . ' ae.' . self::COL_ID . ' AS id,'
            . ' ae.' . self::COL_COOP_ID . ' AS id_cooperativa,'
            . ' c.' . self::COL_COOP_NOMBRE . ' AS cooperativa,'
            . ' ae.' . self::COL_TITULO . ' AS titulo,'
            . ' ae.' . self::COL_DESCRIPCION . ' AS descripcion,'
            . ' ae.' . self::COL_FECHA . ' AS fecha_evento,'
            . ' ae.' . self::COL_TELF . ' AS telefono_contacto,'
            . ' ae.' . self::COL_MAIL . ' AS email_contacto,'
            . ' ae.' . self::COL_ESTADO . ' AS estado'
            . ' FROM ' . self::TABLE . ' ae'
            . ' JOIN ' . self::TABLE_COOPS . ' c ON c.' . self::COL_COOP_ID . ' = ae.' . self::COL_COOP_ID
            . $whereSql
            . ' ORDER BY ae.' . self::COL_FECHA . ' DESC'
            . ' LIMIT :limit OFFSET :offset';

        $st = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();

        $rows = $st->fetchAll();
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id'                => isset($row['id']) ? (int)$row['id'] : 0,
                'id_cooperativa'    => isset($row['id_cooperativa']) ? (int)$row['id_cooperativa'] : 0,
                'cooperativa'       => isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
                'titulo'            => isset($row['titulo']) ? (string)$row['titulo'] : '',
                'descripcion'       => array_key_exists('descripcion', $row) && $row['descripcion'] !== null ? (string)$row['descripcion'] : null,
                'fecha_evento'      => isset($row['fecha_evento']) ? (string)$row['fecha_evento'] : '',
                'telefono_contacto' => array_key_exists('telefono_contacto', $row) && $row['telefono_contacto'] !== null ? (string)$row['telefono_contacto'] : null,
                'email_contacto'    => array_key_exists('email_contacto', $row) && $row['email_contacto'] !== null ? (string)$row['email_contacto'] : null,
                'estado'            => isset($row['estado']) ? (string)$row['estado'] : '',
            ];
        }

        return [
            'data'      => $data,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
        ];
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT'
            . ' ae.' . self::COL_ID . ' AS id,'
            . ' ae.' . self::COL_COOP_ID . ' AS id_cooperativa,'
            . ' c.' . self::COL_COOP_NOMBRE . ' AS cooperativa,'
            . ' ae.' . self::COL_TITULO . ' AS titulo,'
            . ' ae.' . self::COL_DESCRIPCION . ' AS descripcion,'
            . ' ae.' . self::COL_FECHA . ' AS fecha_evento,'
            . ' ae.' . self::COL_TELF . ' AS telefono_contacto,'
            . ' ae.' . self::COL_MAIL . ' AS email_contacto,'
            . ' ae.' . self::COL_ESTADO . ' AS estado,'
            . ' ae.' . self::COL_CREATED_AT . ' AS created_at,'
            . ' ae.' . self::COL_UPDATED_AT . ' AS updated_at'
            . ' FROM ' . self::TABLE . ' ae'
            . ' JOIN ' . self::TABLE_COOPS . ' c ON c.' . self::COL_COOP_ID . ' = ae.' . self::COL_COOP_ID
            . ' WHERE ae.' . self::COL_ID . ' = :id'
            . ' LIMIT 1';

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();

        if (!$row) {
            return null;
        }

        return [
            'id'                => (int)$row['id'],
            'id_cooperativa'    => (int)$row['id_cooperativa'],
            'cooperativa'       => (string)$row['cooperativa'],
            'titulo'            => (string)$row['titulo'],
            'descripcion'       => $row['descripcion'] !== null ? (string)$row['descripcion'] : null,
            'fecha_evento'      => (string)$row['fecha_evento'],
            'telefono_contacto' => $row['telefono_contacto'] !== null ? (string)$row['telefono_contacto'] : null,
            'email_contacto'    => $row['email_contacto'] !== null ? (string)$row['email_contacto'] : null,
            'estado'            => (string)$row['estado'],
            'created_at'        => $row['created_at'] !== null ? (string)$row['created_at'] : null,
            'updated_at'        => $row['updated_at'] !== null ? (string)$row['updated_at'] : null,
        ];
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO ' . self::TABLE
            . ' (' . self::COL_COOP_ID . ', ' . self::COL_TITULO . ', ' . self::COL_DESCRIPCION . ', '
            . self::COL_FECHA . ', ' . self::COL_TELF . ', ' . self::COL_MAIL . ', ' . self::COL_ESTADO . ')
            VALUES (:cooperativa, :titulo, :descripcion, :fecha, :telefono, :email, :estado)
            RETURNING ' . self::COL_ID;

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':cooperativa', (int)$data['id_cooperativa'], PDO::PARAM_INT);
        $st->bindValue(':titulo', (string)$data['titulo'], PDO::PARAM_STR);
        $this->bindNullable($st, ':descripcion', $data['descripcion'] ?? null);
        $st->bindValue(':fecha', (string)$data['fecha_evento'], PDO::PARAM_STR);
        $this->bindNullable($st, ':telefono', $data['telefono_contacto'] ?? null);
        $this->bindNullable($st, ':email', $data['email_contacto'] ?? null);
        $st->bindValue(':estado', (string)$data['estado'], PDO::PARAM_STR);

        $st->execute();
        $id = $st->fetchColumn();

        return (int)$id;
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): void
    {
        $sql = 'UPDATE ' . self::TABLE
            . ' SET ' . self::COL_COOP_ID . ' = :cooperativa,'
            . ' ' . self::COL_TITULO . ' = :titulo,'
            . ' ' . self::COL_DESCRIPCION . ' = :descripcion,'
            . ' ' . self::COL_FECHA . ' = :fecha,'
            . ' ' . self::COL_TELF . ' = :telefono,'
            . ' ' . self::COL_MAIL . ' = :email,'
            . ' ' . self::COL_ESTADO . ' = :estado,'
            . ' ' . self::COL_UPDATED_AT . ' = NOW()'
            . ' WHERE ' . self::COL_ID . ' = :id';

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':cooperativa', (int)$data['id_cooperativa'], PDO::PARAM_INT);
        $st->bindValue(':titulo', (string)$data['titulo'], PDO::PARAM_STR);
        $this->bindNullable($st, ':descripcion', $data['descripcion'] ?? null);
        $st->bindValue(':fecha', (string)$data['fecha_evento'], PDO::PARAM_STR);
        $this->bindNullable($st, ':telefono', $data['telefono_contacto'] ?? null);
        $this->bindNullable($st, ':email', $data['email_contacto'] ?? null);
        $st->bindValue(':estado', (string)$data['estado'], PDO::PARAM_STR);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    public function updateEstado(int $id, string $estado): void
    {
        $sql = 'UPDATE ' . self::TABLE
            . ' SET ' . self::COL_ESTADO . ' = :estado,'
            . ' ' . self::COL_UPDATED_AT . ' = NOW()'
            . ' WHERE ' . self::COL_ID . ' = :id';

        $st = $this->pdo->prepare($sql);
        $st->bindValue(':estado', $estado, PDO::PARAM_STR);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    public function delete(int $id): void
    {
        $sql = 'DELETE FROM ' . self::TABLE . ' WHERE ' . self::COL_ID . ' = :id';
        $st  = $this->pdo->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
    }

    private function bindNullable(PDOStatement $st, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $st->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }

        $st->bindValue($param, (string)$value, PDO::PARAM_STR);
    }
}
