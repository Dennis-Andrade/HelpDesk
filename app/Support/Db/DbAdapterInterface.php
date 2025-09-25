<?php
declare(strict_types=1);

namespace App\Support\Db;

interface DbAdapterInterface
{
    public function begin(): void;

    public function commit(): void;

    public function rollBack(): void;

    /**
     * Ejecuta sentencias de escritura.
     * Devuelve filas si el SQL incluye RETURNING.
     *
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>|null
     */
    public function execute(string $sql, array $params = []);

    /**
     * Obtiene todas las filas como arreglos asociativos.
     *
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array;

    /**
     * Obtiene una fila o null si no existe.
     *
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []);
}
