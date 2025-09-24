<?php
declare(strict_types=1);

namespace App\Repositories\Shared;

use function Config\db;

final class UbicacionesRepository
{
    /** @return array<int, array{id:int, nombre:string}> */
    public function provincias(): array
    {
        $st = db()->query('SELECT id, nombre FROM public.provincia ORDER BY nombre');
        return $st->fetchAll();
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public function cantonesPorProvincia(int $provinciaId): array
    {
        $st = db()->prepare('SELECT id, nombre FROM public.canton WHERE provincia_id = :p ORDER BY nombre');
        $st->execute([':p'=>$provinciaId]);
        return $st->fetchAll();
    }
}
