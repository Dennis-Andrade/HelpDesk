<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;

final class EliminarEntidadService
{
    private $repo;
    public function __construct() { $this->repo = new EntidadRepository(); }
    public function eliminar(int $id): bool { return $this->repo->delete($id); }
}
