<?php
declare(strict_types=1);

namespace App\Repositories\Comercial;

use App\Repositories\BaseRepository;
use PDO;

final class DashboardRepository extends BaseRepository
{
    private const T_COOP        = 'public.cooperativas';
    private const COL_ID        = 'id_cooperativa';
    private const COL_SEGMENTO  = 'id_segmento';
    private const COL_ACTIVA    = 'activa';
    private const COL_FECHA_REG = 'fecha_registro';

    private const T_SEGMENTO    = 'public.segmentos';
    private const SEG_ID        = 'id_segmento';
    private const SEG_NOMBRE    = 'nombre_segmento';

    public function totalCooperativas(): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM ' . self::T_COOP;
        $row = $this->db->fetch($sql);

        return isset($row['total']) ? (int)$row['total'] : 0;
    }

    /**
     * @return array<int,array{nombre_segmento:string,cantidad:int}>
     */
    public function cooperativasPorSegmento(): array
    {
        $sql = implode("\n", array(
            'SELECT',
            '    COALESCE(seg.' . self::SEG_NOMBRE . ', ' . "'Sin segmento'" . ') AS nombre_segmento,',
            '    COUNT(c.' . self::COL_ID . ') AS cantidad',
            'FROM ' . self::T_COOP . ' c',
            'LEFT JOIN ' . self::T_SEGMENTO . ' seg',
            '  ON seg.' . self::SEG_ID . ' = c.' . self::COL_SEGMENTO,
            'GROUP BY 1',
            'ORDER BY 1'
        ));

        $rows = $this->db->fetchAll($sql);

        $data = array();
        foreach ($rows as $row) {
            $nombre = isset($row['nombre_segmento']) ? (string)$row['nombre_segmento'] : 'Sin segmento';
            $cantidad = isset($row['cantidad']) ? (int)$row['cantidad'] : 0;
            $data[] = array(
                'nombre_segmento' => $nombre,
                'cantidad'        => $cantidad,
            );
        }

        return $data;
    }

    /**
     * @return array{activas:int,inactivas:int}
     */
    public function cooperativasPorEstado(): array
    {
        $sql = implode("\n", array(
            'SELECT',
            '    COUNT(*) FILTER (WHERE c.' . self::COL_ACTIVA . ' IS TRUE) AS activas,',
            '    COUNT(*) FILTER (WHERE c.' . self::COL_ACTIVA . ' IS DISTINCT FROM TRUE) AS inactivas',
            'FROM ' . self::T_COOP . ' c'
        ));

        $row = $this->db->fetch($sql) ?: array();

        return array(
            'activas'   => isset($row['activas']) ? (int)$row['activas'] : 0,
            'inactivas' => isset($row['inactivas']) ? (int)$row['inactivas'] : 0,
        );
    }

    /**
     * @return array<int,array{mes:string,cantidad:int}>
     */
    public function registrosMensuales(int $months): array
    {
        $months = max(1, $months);

        $sql = implode("\n", array(
            'SELECT',
            "    to_char(date_trunc('month', c." . self::COL_FECHA_REG . "), 'YYYY-MM') AS mes,",
            '    COUNT(*) AS cantidad',
            'FROM ' . self::T_COOP . ' c',
            'WHERE c.' . self::COL_FECHA_REG . ' >= (CURRENT_DATE - make_interval(months => :months))',
            'GROUP BY date_trunc(\'month\', c.' . self::COL_FECHA_REG . ')',
            'ORDER BY date_trunc(\'month\', c.' . self::COL_FECHA_REG . ')'
        ));

        $rows = $this->db->fetchAll($sql, array(
            ':months' => array($months, PDO::PARAM_INT),
        ));

        $data = array();
        foreach ($rows as $row) {
            $mes = isset($row['mes']) ? (string)$row['mes'] : '';
            $cantidad = isset($row['cantidad']) ? (int)$row['cantidad'] : 0;
            if ($mes === '') {
                continue;
            }
            $data[] = array(
                'mes'      => $mes,
                'cantidad' => $cantidad,
            );
        }

        return $data;
    }
}
