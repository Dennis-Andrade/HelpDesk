<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Services\Contabilidad\DashboardService;
use App\Services\Shared\Breadcrumbs;
use App\Support\Logger;
use function view;

final class DashboardController
{
    private DashboardService $dashboard;

    public function __construct(?DashboardService $dashboard = null)
    {
        $this->dashboard = $dashboard ?? new DashboardService();
    }

    public function index(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['label' => 'Dashboard'],
        ]);

        $error = null;
        $stats = [
            'cards'     => [
                'contratos_activos'  => 0,
                'servicios_vigentes' => 0,
                'facturas_mes'       => 0,
                'pagos_pendientes'   => 0,
            ],
            'servicios' => ['labels' => [], 'counts' => []],
            'estados'   => ['labels' => [], 'counts' => []],
            'mensual'   => ['labels' => [], 'amounts' => [], 'facturas' => []],
        ];

        try {
            $stats = $this->dashboard->obtenerEstadisticas();
        } catch (\Throwable $e) {
            $error = 'No se pudieron cargar las métricas de contabilidad.';
            Logger::error($e, 'Contabilidad\\DashboardController::index');
        }

        view('contabilidad/dashboard/index', [
            'title'  => 'Contabilidad · Dashboard',
            'crumbs' => $crumbs,
            'stats'  => $stats,
            'error'  => $error,
        ]);
    }
}
