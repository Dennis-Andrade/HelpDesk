<?php
namespace App\Controllers\Comercial;

use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\MetricsService;
use App\Services\Shared\Pagination;
use App\Services\Comercial\BuscarEntidadesService;

final class DashboardController
{
  public function index(): void
  {
    $crumbs = Breadcrumbs::make([
      ['href'=>'/comercial', 'label'=>'Comercial'],
      ['label'=>'Dashboard']
    ]);
    $metrics = (new MetricsService())->forModule('comercial'); // placeholder
    view('comercial/dashboard/index', compact('crumbs','metrics') + ['title'=>'Comercial · Dashboard']);
  }

  // Ejemplo: listado paginado de Entidades Financieras
  public function entidades(): void
  {
    $crumbs = Breadcrumbs::make([
      ['href'=>'/comercial', 'label'=>'Comercial'],
      ['label'=>'Entidades Financieras']
    ]);

    $q = trim($_GET['q'] ?? '');
    $pg = Pagination::fromRequest($_GET, 1, 20, 100); // page default 1, perPage 20, max 100
    $result = (new BuscarEntidadesService())->buscar($q, $pg->page, $pg->perPage);

    view('comercial/entidades/index', [
      'title'   => 'Comercial · Entidades Financieras',
      'crumbs'  => $crumbs,
      'items'   => $result['items'],
      'total'   => $result['total'],
      'page'    => $result['page'],
      'perPage' => $result['perPage'],
      'q'       => $q
    ]);
  }
}
