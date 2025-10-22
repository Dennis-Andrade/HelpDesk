<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Repositories\Comercial\AgendaRepository;
use App\Repositories\Comercial\SeguimientoRepository as ComercialSeguimientoRepository;
use App\Repositories\Contabilidad\SeguimientoRepository as ContabilidadSeguimientoRepository;
use DateTimeImmutable;
use RuntimeException;

final class CalendarService
{
    private AgendaRepository $agendaRepo;
    private ComercialSeguimientoRepository $comercialSeguimiento;
    private ContabilidadSeguimientoRepository $contabilidadSeguimiento;

    public function __construct(
        ?AgendaRepository $agendaRepo = null,
        ?ComercialSeguimientoRepository $comercialSeguimiento = null,
        ?ContabilidadSeguimientoRepository $contabilidadSeguimiento = null
    ) {
        $this->agendaRepo = $agendaRepo ?? new AgendaRepository();
        $this->comercialSeguimiento = $comercialSeguimiento ?? new ComercialSeguimientoRepository();
        $this->contabilidadSeguimiento = $contabilidadSeguimiento ?? new ContabilidadSeguimientoRepository();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function events(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if ($to < $from) {
            throw new RuntimeException('El rango de fechas del calendario es inválido.');
        }

        $desde = $from->format('Y-m-d');
        $hasta = $to->format('Y-m-d');

        $events = [];

        foreach ($this->agendaRepo->eventosCalendario($desde, $hasta) as $row) {
            $mapped = $this->mapAgendaEvent($row);
            if ($mapped !== null) {
                $events[] = $mapped;
            }
        }

        foreach ($this->comercialSeguimiento->eventosCalendario($desde, $hasta) as $row) {
            $mapped = $this->mapComercialSeguimiento($row);
            if ($mapped !== null) {
                $events[] = $mapped;
            }
        }

        foreach ($this->contabilidadSeguimiento->eventosCalendario($desde, $hasta) as $row) {
            $mapped = $this->mapContabilidadSeguimiento($row);
            if ($mapped !== null) {
                $events[] = $mapped;
            }
        }

        usort($events, static function (array $a, array $b): int {
            return strcmp($a['start'], $b['start']);
        });

        return $events;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    private function mapAgendaEvent(array $row): ?array
    {
        $start = isset($row['fecha_evento']) ? trim((string)$row['fecha_evento']) : '';
        if ($start === '') {
            return null;
        }

        $title = trim((string)($row['titulo'] ?? 'Agenda'));
        $entity = trim((string)($row['coop_nombre'] ?? 'Entidad'));
        $nota = trim((string)($row['nota'] ?? ''));

        return [
            'id'          => 'agenda-' . (isset($row['id']) ? (int)$row['id'] : 0),
            'module'      => 'Comercial',
            'source'      => 'Agenda',
            'title'       => $title !== '' ? $title : 'Agenda',
            'entity'      => $entity,
            'start'       => $start,
            'end'         => $start,
            'badge'       => 'Agenda',
            'notes'       => $nota,
            'url'         => '/comercial/agenda',
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    private function mapComercialSeguimiento(array $row): ?array
    {
        $start = isset($row['fecha_actividad']) ? trim((string)$row['fecha_actividad']) : '';
        if ($start === '') {
            return null;
        }
        $end = isset($row['fecha_finalizacion']) && $row['fecha_finalizacion'] !== null
            ? trim((string)$row['fecha_finalizacion'])
            : $start;

        $tipo = trim((string)($row['tipo'] ?? 'Seguimiento'));
        $descripcion = trim((string)($row['descripcion'] ?? ''));
        $entity = trim((string)($row['cooperativa'] ?? 'Entidad'));

        return [
            'id'          => 'com-seg-' . (isset($row['id']) ? (int)$row['id'] : 0),
            'module'      => 'Comercial',
            'source'      => 'Seguimiento',
            'title'       => $tipo,
            'entity'      => $entity,
            'description' => $this->shorten($descripcion),
            'start'       => $start,
            'end'         => $end,
            'badge'       => $tipo,
            'url'         => '/comercial/eventos',
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>|null
     */
    private function mapContabilidadSeguimiento(array $row): ?array
    {
        $start = isset($row['fecha_actividad']) ? trim((string)$row['fecha_actividad']) : '';
        if ($start === '') {
            return null;
        }
        $end = isset($row['fecha_finalizacion']) && $row['fecha_finalizacion'] !== null
            ? trim((string)$row['fecha_finalizacion'])
            : $start;

        $tipo = trim((string)($row['tipo'] ?? 'Seguimiento'));
        $medio = trim((string)($row['medio'] ?? ''));
        $descripcion = trim((string)($row['descripcion'] ?? ''));
        $entity = trim((string)($row['cooperativa'] ?? 'Entidad'));

        $title = $tipo !== '' ? $tipo : 'Seguimiento';
        if ($medio !== '') {
            $title .= ' (' . $medio . ')';
        }

        return [
            'id'          => 'contab-seg-' . (isset($row['id']) ? (int)$row['id'] : 0),
            'module'      => 'Contabilidad',
            'source'      => 'Seguimiento',
            'title'       => $title,
            'entity'      => $entity,
            'description' => $this->shorten($descripcion),
            'start'       => $start,
            'end'         => $end,
            'badge'       => $tipo !== '' ? $tipo : 'Seguimiento',
            'url'         => '/contabilidad/seguimiento',
        ];
    }

    private function shorten(string $text, int $length = 120): string
    {
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length - 1) . '…';
    }
}
