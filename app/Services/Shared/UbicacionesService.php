<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Repositories\Shared\UbicacionesRepository;

/**
 * Provincias y Cantones (Ecuador).
 * Orquesta el acceso al repositorio sin meter SQL aquí.
 */
final class UbicacionesService
{
    /** @var UbicacionesRepository */
    private $repo;

    public function __construct(?UbicacionesRepository $repo = null)
    {
        $this->repo = $repo ?: new UbicacionesRepository();
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public function provincias(): array
    {
        return $this->repo->provincias();
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public function cantones(int $provinciaId): array
    {
        if ($provinciaId <= 0) return [];
        return $this->repo->cantones($provinciaId);
    }
}
