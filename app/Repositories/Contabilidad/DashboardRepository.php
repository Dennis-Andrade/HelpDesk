<?php
declare(strict_types=1);

namespace App\Repositories\Contabilidad;

use App\Repositories\BaseRepository;

final class DashboardRepository extends BaseRepository
{
    /**
     * @return array{contratos_activos:int,servicios_vigentes:int,facturas_mes:int,pagos_pendientes:int}
     */
    public function resumenCards(): array
    {
        $sql = <<<SQL
WITH contratos AS (
    SELECT
        COALESCE(SUM(CASE WHEN cs.activo IS TRUE THEN 1 ELSE 0 END), 0) AS contratos_activos
    FROM public.contrataciones_servicios cs
),
servicios_activos AS (
    SELECT DISTINCT ps.id_servicio
      FROM public.contrato_servicios_detalle ps
      JOIN public.contrataciones_servicios cs ON cs.id_contratacion = ps.id_contratacion
     WHERE cs.activo IS TRUE
    UNION
    SELECT DISTINCT cs.id_servicio
      FROM public.contrataciones_servicios cs
     WHERE cs.activo IS TRUE
),
facturas AS (
    SELECT COALESCE(COUNT(*), 0) AS facturas_mes
    FROM public.contabilidad_facturacion_historial h
    WHERE h.fecha_emision IS NOT NULL
      AND date_trunc('month', h.fecha_emision) = date_trunc('month', CURRENT_DATE)
),
pendientes AS (
    SELECT COALESCE(COUNT(*), 0) AS pagos_pendientes
    FROM public.contabilidad_facturacion_historial h
    WHERE lower(COALESCE(NULLIF(TRIM(h.estado), ''), 'pendiente')) IN ('pendiente', 'vencido')
)
SELECT
    contratos.contratos_activos,
    (SELECT COUNT(*) FROM servicios_activos) AS servicios_vigentes,
    facturas.facturas_mes,
    pendientes.pagos_pendientes
FROM contratos, facturas, pendientes
SQL;

        $row = $this->db->fetch($sql) ?? [];

        return [
            'contratos_activos'   => (int)($row['contratos_activos'] ?? 0),
            'servicios_vigentes'  => (int)($row['servicios_vigentes'] ?? 0),
            'facturas_mes'        => (int)($row['facturas_mes'] ?? 0),
            'pagos_pendientes'    => (int)($row['pagos_pendientes'] ?? 0),
        ];
    }

    /**
     * @return array<int,array{servicio:string,total:int}>
     */
    public function contratosPorServicio(): array
    {
        $sql = <<<SQL
WITH servicios_asignados AS (
    SELECT ps.id_servicio, ps.id_contratacion
      FROM public.contrato_servicios_detalle ps
    UNION
    SELECT cs.id_servicio, cs.id_contratacion
      FROM public.contrataciones_servicios cs
     WHERE NOT EXISTS (
               SELECT 1
                 FROM public.contrato_servicios_detalle ps2
                WHERE ps2.id_contratacion = cs.id_contratacion
           )
)
SELECT
    COALESCE(NULLIF(TRIM(serv.nombre_servicio), ''), 'Sin servicio') AS servicio,
    COUNT(*) AS total
FROM servicios_asignados sa
LEFT JOIN public.servicios serv ON serv.id_servicio = sa.id_servicio
GROUP BY servicio
ORDER BY total DESC, servicio ASC
SQL;

        $rows = $this->db->fetchAll($sql);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'servicio' => (string)($row['servicio'] ?? 'Sin servicio'),
                'total'    => (int)($row['total'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @return array<int,array{estado:string,total:int}>
     */
    public function pagosPorEstado(): array
    {
        $sql = <<<SQL
SELECT
    lower(COALESCE(NULLIF(TRIM(h.estado), ''), 'pendiente')) AS estado,
    COUNT(*) AS total
FROM public.contabilidad_facturacion_historial h
GROUP BY estado
ORDER BY total DESC, estado ASC
SQL;

        $rows = $this->db->fetchAll($sql);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'estado' => (string)($row['estado'] ?? 'pendiente'),
                'total'  => (int)($row['total'] ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @return array<int,array{periodo:string,total:float,facturas:int}>
     */
    public function facturacionMensual(): array
    {
        $sql = <<<SQL
SELECT
    TO_CHAR(date_trunc('month', h.fecha_emision), 'YYYY-MM') AS periodo,
    SUM(COALESCE(h.monto_total, 0)) AS total,
    COUNT(*) AS facturas
FROM public.contabilidad_facturacion_historial h
WHERE h.fecha_emision IS NOT NULL
  AND date_trunc('month', h.fecha_emision) >= date_trunc('month', CURRENT_DATE) - INTERVAL '5 months'
GROUP BY periodo
ORDER BY periodo ASC
SQL;

        $rows = $this->db->fetchAll($sql);

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'periodo'  => (string)($row['periodo'] ?? ''),
                'total'    => isset($row['total']) ? (float)$row['total'] : 0.0,
                'facturas' => (int)($row['facturas'] ?? 0),
            ];
        }

        return $items;
    }
}
