<?php
declare(strict_types=1);

namespace App\Repositories\Shared;

use App\Repositories\BaseRepository;
use RuntimeException;

final class UbicacionesRepository extends BaseRepository
{
    /** @return array<int, array{id:int, nombre:string}> */
    public function provincias(): array
    {
        $sql = 'SELECT id, nombre FROM public.provincia ORDER BY nombre';
        try {
            return $this->db->fetchAll($sql);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener provincias.', 0, $e);
        }
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public function cantonesPorProvincia(int $provinciaId): array
    {
        $sql = 'SELECT id, nombre FROM public.canton WHERE provincia_id = :p ORDER BY nombre';
        try {
            return $this->db->fetchAll($sql, array(':p' => array($provinciaId, \PDO::PARAM_INT)));
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener cantones.', 0, $e);
        }
    }
}
