<?php
declare(strict_types=1);

namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use Config\Cnxn;

final class EliminarEntidadService
{
    private EntidadRepository $repo;

    public function __construct(?EntidadRepository $repo = null)
    {
        $this->repo = $repo ?? new EntidadRepository(Cnxn::pdo());
    }

    public function eliminar(int $id): bool
    {
        $this->repo->delete($id);
        return true;
    }
}
