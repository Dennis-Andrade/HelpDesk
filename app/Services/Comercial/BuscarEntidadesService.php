<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;

final class BuscarEntidadesService
{
    /** @return array{items:array, total:int, page:int, perPage:int} */
    public function buscar(string $q, int $page, int $perPage = 15): array
    {
        $repo = new EntidadRepository();
        return $repo->search($q, $page, $perPage);
    }
}
