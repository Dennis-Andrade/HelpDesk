<?php
declare(strict_types=1);

namespace App\Services\Contabilidad;

use App\Repositories\Contabilidad\DashboardRepository;
use App\Support\Logger;
use DateInterval;
use DateTimeImmutable;

final class DashboardService
{
    private const CARD_DEFAULTS = [
        'contratos_activos'  => 0,
        'servicios_vigentes' => 0,
        'facturas_mes'       => 0,
        'pagos_pendientes'   => 0,
    ];

    private DashboardRepository $repository;

    public function __construct(?DashboardRepository $repository = null)
    {
        $this->repository = $repository ?? new DashboardRepository();
    }

    /**
     * @return array{
     *   cards: array{
     *     contratos_activos:int,
     *     servicios_vigentes:int,
     *     facturas_mes:int,
     *     pagos_pendientes:int
     *   },
     *   servicios: array{labels:array<int,string>,counts:array<int,int>},
     *   estados: array{labels:array<int,string>,counts:array<int,int>},
     *   mensual: array{labels:array<int,string>,amounts:array<int,float>,facturas:array<int,int>}
     * }
     */
    public function obtenerEstadisticas(): array
    {
        $cards = self::CARD_DEFAULTS;

        try {
            $cards = array_replace($cards, $this->repository->resumenCards());
        } catch (\Throwable $e) {
            Logger::error($e, 'Contabilidad\\DashboardService::resumenCards');
        }

        $servicios = [];
        try {
            $servicios = $this->repository->contratosPorServicio();
        } catch (\Throwable $e) {
            Logger::error($e, 'Contabilidad\\DashboardService::contratosPorServicio');
        }

        $estados = [];
        try {
            $estados = $this->repository->pagosPorEstado();
        } catch (\Throwable $e) {
            Logger::error($e, 'Contabilidad\\DashboardService::pagosPorEstado');
        }

        $mensual = [];
        try {
            $mensual = $this->repository->facturacionMensual();
        } catch (\Throwable $e) {
            Logger::error($e, 'Contabilidad\\DashboardService::facturacionMensual');
        }

        return [
            'cards'     => $cards,
            'servicios' => $this->normalizarServicios($servicios),
            'estados'   => $this->normalizarEstados($estados),
            'mensual'   => $this->normalizarMensual($mensual),
        ];
    }

    /**
     * @param array<int,array{servicio:string,total:int}> $rows
     * @return array{labels:array<int,string>,counts:array<int,int>}
     */
    private function normalizarServicios(array $rows): array
    {
        $rows = array_slice($rows, 0, 8);
        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $labels[] = (string)($row['servicio'] ?? 'Sin servicio');
            $counts[] = (int)($row['total'] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * @param array<int,array{estado:string,total:int}> $rows
     * @return array{labels:array<int,string>,counts:array<int,int>}
     */
    private function normalizarEstados(array $rows): array
    {
        $labels = [];
        $counts = [];

        foreach ($rows as $row) {
            $estado = (string)($row['estado'] ?? 'pendiente');
            $estado = str_replace('_', ' ', $estado);
            $labels[] = ucwords($estado);
            $counts[] = (int)($row['total'] ?? 0);
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * @param array<int,array{periodo:string,total:float,facturas:int}> $rows
     * @return array{labels:array<int,string>,amounts:array<int,float>,facturas:array<int,int>}
     */
    private function normalizarMensual(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = (string)($row['periodo'] ?? '');
            if ($key === '') {
                continue;
            }
            $map[$key] = [
                'total'    => isset($row['total']) ? (float)$row['total'] : 0.0,
                'facturas' => (int)($row['facturas'] ?? 0),
            ];
        }

        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $base = new DateTimeImmutable('first day of this month');
        $labels = [];
        $amounts = [];
        $facturas = [];

        for ($i = 5; $i >= 0; $i--) {
            $current = $base->sub(new DateInterval('P' . $i . 'M'));
            $key = $current->format('Y-m');
            $monthNum = (int)$current->format('n');

            $labels[] = ($monthNames[$monthNum] ?? $current->format('F')) . ' ' . $current->format('Y');
            $amounts[] = $map[$key]['total'] ?? 0.0;
            $facturas[] = $map[$key]['facturas'] ?? 0;
        }

        return [
            'labels'  => $labels,
            'amounts' => $amounts,
            'facturas'=> $facturas,
        ];
    }
}
