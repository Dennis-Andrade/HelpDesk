<?php
declare(strict_types=1);

namespace App\Controllers\Shared;

use App\Services\Shared\UbicacionesService;

final class UbicacionesController
{
    /** @var UbicacionesService */
    private $svc;

    public function __construct()
    {
        // Sin contenedor DI: instancia directa
        $this->svc = new UbicacionesService();
    }

    // GET /shared/cantones?provincia_id=##
    public function cantones(): void
    {
        $prov = (int)($_GET['provincia_id'] ?? 0);
        $data = $prov > 0 ? $this->svc->cantones($prov) : [];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
    }
}
