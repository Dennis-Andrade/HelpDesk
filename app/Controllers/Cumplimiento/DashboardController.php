<?php
namespace App\Controllers\Cumplimiento;

use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\MetricsService;

final class DashboardController
{
  public function index(): void
  {
    $crumbs = Breadcrumbs::make([
      ['href'=>'/contabilidad', 'label'=>'Contabilidad'],
      ['label'=>'Dashboard']
    ]);
    $metrics = (new MetricsService())->forModule('contabilidad');
    view('contabilidad/dashboard/index', compact('crumbs','metrics') + ['title'=>'Contabilidad · Dashboard']);
  }
}
