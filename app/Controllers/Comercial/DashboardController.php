<?php
namespace App\Controllers\Comercial;

use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Comercial\DashboardService;

final class DashboardController
{
  public function index(): void
  {
    $crumbs = Breadcrumbs::make([
      ['href'=>'/comercial', 'label'=>'Comercial'],
      ['label'=>'Dashboard']
    ]);
    $service = new DashboardService();
    $error   = null;
    $stats   = array('cards'=>array(), 'segmentos'=>array(), 'estado'=>array('activas'=>0,'inactivas'=>0), 'mensual'=>array('labels'=>array(), 'counts'=>array()));

    try {
      $stats = $service->obtenerEstadisticas();
    } catch (\Throwable $e) {
      $error = 'Error al cargar datos estadísticos: ' . $e->getMessage();
    }

    view('comercial/dashboard/index', [
      'title'    => 'Comercial · Dashboard',
      'crumbs'   => $crumbs,
      'stats'    => $stats,
      'error'    => $error,
    ]);
  }

  // Ejemplo: listado paginado de Entidades
  public function entidades(): void
  {
    $crumbs = Breadcrumbs::make([
      ['href'=>'/comercial', 'label'=>'Comercial'],
      ['label'=>'Entidades']
    ]);

    $q = trim($_GET['q'] ?? '');
    $pg = Pagination::fromRequest($_GET, 1, 20, 100); // page default 1, perPage 20, max 100
    $result = (new BuscarEntidadesService())->buscar($q, $pg->page, $pg->perPage);

    view('comercial/entidades/index', [
      'title'   => 'Comercial · Entidades',
      'crumbs'  => $crumbs,
      'items'   => $result['items'],
      'total'   => $result['total'],
      'page'    => $result['page'],
      'perPage' => $result['perPage'],
      'q'       => $q
    ]);
  }
}
