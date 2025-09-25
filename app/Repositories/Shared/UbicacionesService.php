<?php
namespace App\Services\Shared;
use App\Repositories\Shared\UbicacionesRepository;

final class UbicacionesService
{
    public function __construct(private UbicacionesRepository $repo){}

    public function provincias(): array { return $this->repo->provincias(); }
    public function cantones(int $provId): array { return $this->repo->cantonesPorProvincia($provId); }
}
