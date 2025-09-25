<?php
namespace App\Services\Shared;

final class MetricsService
{
  public function forModule(string $module): array
  {
    // TODO: leer de repositorios/consultas reales
    switch ($module) {
      case 'comercial':
        return [
          ['label'=>'Entidades', 'value'=>128],
          ['label'=>'Contactos', 'value'=>512],
          ['label'=>'Eventos (mes)', 'value'=>7],
          ['label'=>'Incidencias abiertas', 'value'=>3],
        ];
      case 'contabilidad':
        return [
          ['label'=>'Contratos activos', 'value'=>42],
          ['label'=>'Productos', 'value'=>9],
          ['label'=>'Facturas del mes', 'value'=>134],
          ['label'=>'Tickets “Switch”', 'value'=>5],
        ];
      default:
        return [];
    }
  }
}
