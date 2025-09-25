<?php
declare(strict_types=1);

namespace App\Support\Db;

final class PgAdapter implements DbAdapterInterface
{
    /** @var resource */
    private $conn;

    public function __construct($pgConn)
    {
        $this->conn = $pgConn;
    }

    public function begin(): void
    {
        pg_query($this->conn, 'BEGIN');
    }

    public function commit(): void
    {
        pg_query($this->conn, 'COMMIT');
    }

    public function rollBack(): void
    {
        pg_query($this->conn, 'ROLLBACK');
    }

    public function execute(string $sql, array $params = [])
    {
        $res = $this->query($sql, $params);
        if (stripos($sql, 'returning') !== false) {
            $rows = array();
            while ($row = pg_fetch_assoc($res)) {
                $rows[] = $row;
            }
            return $rows;
        }
        return null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $res  = $this->query($sql, $params);
        $rows = array();
        while ($row = pg_fetch_assoc($res)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetch(string $sql, array $params = [])
    {
        $res = $this->query($sql, $params);
        $row = pg_fetch_assoc($res);
        return $row === false ? null : $row;
    }

    public function listen(string $channel): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $channel)) {
            throw new \InvalidArgumentException('Canal inválido');
        }
        pg_query($this->conn, 'LISTEN ' . $channel);
    }

    public function getNotify(): ?array
    {
        $notify = pg_get_notify($this->conn, PGSQL_ASSOC);
        return $notify === false ? null : $notify;
    }

    public function getResource()
    {
        return $this->conn;
    }

    private function query(string $sql, array $params)
    {
        if (empty($params)) {
            $res = pg_query($this->conn, $sql);
        } else {
            $res = pg_query_params($this->conn, $sql, $this->normalizeParams($params));
        }
        if ($res === false) {
            throw new \RuntimeException(pg_last_error($this->conn));
        }
        return $res;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<int, string|null>
     */
    private function normalizeParams(array $params): array
    {
        $normalized = array();
        foreach ($params as $value) {
            if (is_array($value)) {
                $value = $value[0];
            }
            if (is_bool($value)) {
                $normalized[] = $value ? 't' : 'f';
            } elseif ($value === null) {
                $normalized[] = null;
            } else {
                $normalized[] = (string) $value;
            }
        }
        return $normalized;
    }
}
