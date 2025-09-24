<?php
declare(strict_types=1);

namespace App\Support\Db;

final class PdoAdapter implements DbAdapterInterface
{
    /** @var \PDO */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function execute(string $sql, array $params = [])
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        if (stripos($sql, 'returning') !== false) {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function fetch(string $sql, array $params = [])
    {
        $stmt = $this->prepareAndExecute($sql, $params);
        $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    private function prepareAndExecute(string $sql, array $params): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $paramName = is_int($key) ? $key + 1 : $key;
            if (is_array($value)) {
                $stmt->bindValue($paramName, $value[0], $this->resolveType($value[0], $value[1] ?? null));
                continue;
            }
            $stmt->bindValue($paramName, $value, $this->resolveType($value));
        }
        $stmt->execute();
        return $stmt;
    }

    private function resolveType($value, ?int $explicit = null): int
    {
        if ($explicit !== null) {
            return $explicit;
        }
        if ($value === null) {
            return \PDO::PARAM_NULL;
        }
        if (is_int($value)) {
            return \PDO::PARAM_INT;
        }
        if (is_bool($value)) {
            return \PDO::PARAM_BOOL;
        }
        return \PDO::PARAM_STR;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
