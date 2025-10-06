<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\DashboardRepository;
use RuntimeException;

final class DashboardService
{
    private DashboardRepository $repository;

    public function __construct(?DashboardRepository $repository = null)
    {
        $this->repository = $repository ?? new DashboardRepository();
    }

    /**
     * @return array{
     *   cards: array{
     *     total:int,
     *     activas:int,
     *     inactivas:int,
     *     porcentaje_activas:int,
     *     porcentaje_inactivas:int,
     *     ultimo_mes:int,
     *     variacion:string
     *   },
     *   segmentos: array<int,array{nombre_segmento:string,cantidad:int}>,
     *   estado: array{activas:int,inactivas:int},
     *   mensual: array{labels:array<int,string>,counts:array<int,int>}
     * }
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $total      = $this->repository->totalCooperativas();
            $estado     = $this->repository->cooperativasPorEstado();
            $segmentos  = $this->repository->cooperativasPorSegmento();
            $mensuales  = $this->repository->registrosMensuales(6);
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al obtener métricas del dashboard', 0, $e);
        }

        $activas   = isset($estado['activas']) ? (int)$estado['activas'] : 0;
        $inactivas = isset($estado['inactivas']) ? (int)$estado['inactivas'] : 0;

        $porcentajeActivas   = $total > 0 ? (int)round(($activas / $total) * 100) : 0;
        $porcentajeInactivas = $total > 0 ? (int)round(($inactivas / $total) * 100) : 0;

        $mensualLabels = array();
        $mensualCounts = array();
        $meses = array('enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre');

        foreach ($mensuales as $row) {
            $mesRaw  = isset($row['mes']) ? (string)$row['mes'] : '';
            $cantidad = isset($row['cantidad']) ? (int)$row['cantidad'] : 0;
            if ($mesRaw === '' || strpos($mesRaw, '-') === false) {
                continue;
            }
            $parts = explode('-', $mesRaw);
            if (count($parts) !== 2) {
                continue;
            }
            $yy = $parts[0];
            $mm = (int)$parts[1];
            $indice = $mm >= 1 && $mm <= 12 ? $mm - 1 : null;
            if ($indice === null) {
                continue;
            }
            $mensualLabels[] = $meses[$indice] . ' ' . $yy;
            $mensualCounts[] = $cantidad;
        }

        $countMens    = count($mensualCounts);
        $ultimoMesCnt = $countMens > 0 ? $mensualCounts[$countMens - 1] : 0;
        $variacionStr = 'Nuevo registro';
        if ($countMens > 1) {
            $actual = $mensualCounts[$countMens - 1];
            $previo = $mensualCounts[$countMens - 2];
            if ($previo !== 0) {
                $cambio = (int)round((($actual - $previo) / $previo) * 100);
            } else {
                $cambio = $actual > 0 ? 100 : 0;
            }
            $variacionStr = abs($cambio) . '% ' . ($cambio >= 0 ? '↑' : '↓');
        }

        return array(
            'cards' => array(
                'total'                 => $total,
                'activas'               => $activas,
                'inactivas'             => $inactivas,
                'porcentaje_activas'    => $porcentajeActivas,
                'porcentaje_inactivas'  => $porcentajeInactivas,
                'ultimo_mes'            => $ultimoMesCnt,
                'variacion'             => $variacionStr,
            ),
            'segmentos' => $segmentos,
            'estado'    => array(
                'activas'   => $activas,
                'inactivas' => $inactivas,
            ),
            'mensual'   => array(
                'labels' => $mensualLabels,
                'counts' => $mensualCounts,
            ),
        );
    }
}
