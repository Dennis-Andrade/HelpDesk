<?php
declare(strict_types=1);

namespace App\Controllers\Shared;

use App\Services\Shared\CalendarService;
use DateTimeImmutable;
use RuntimeException;
use function json_encode;
use function redirect;
use function view;

final class CalendarController
{
    private CalendarService $calendar;

    public function __construct(?CalendarService $calendar = null)
    {
        $this->calendar = $calendar ?? new CalendarService();
    }

    public function index(): void
    {
        $monthParam = isset($_GET['month']) ? trim((string)$_GET['month']) : '';
        $moduleParam = isset($_GET['module']) ? trim((string)$_GET['module']) : '';

        $baseDate = $this->parseMonth($monthParam);
        if ($baseDate === null) {
            $baseDate = new DateTimeImmutable('first day of this month');
        }

        $from = $baseDate->modify('first day of this month');
        $to = $baseDate->modify('last day of this month');

        $events = $this->calendar->events($from, $to);
        if ($moduleParam !== '') {
            $events = array_values(array_filter($events, static function (array $event) use ($moduleParam): bool {
                return strcasecmp($event['module'] ?? '', $moduleParam) === 0;
            }));
        }

        view('shared/calendar/index', [
            'layout'      => 'layout',
            'title'       => 'Calendario unificado',
            'month'       => $from->format('Y-m'),
            'startDate'   => $from->format('Y-m-d'),
            'endDate'     => $to->format('Y-m-d'),
            'module'      => $moduleParam,
            'eventsJson'  => json_encode($events, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function events(): void
    {
        $startParam  = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
        $endParam    = isset($_GET['end']) ? trim((string)$_GET['end']) : '';
        $moduleParam = isset($_GET['module']) ? trim((string)$_GET['module']) : '';

        $from = $this->parseDate($startParam) ?? new DateTimeImmutable('first day of this month');
        $to   = $this->parseDate($endParam) ?? $from->modify('last day of this month');

        if ($to < $from) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => 'Rango de fechas inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $events = $this->calendar->events($from, $to);
        } catch (RuntimeException $e) {
            http_response_code(400);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($moduleParam !== '') {
            $events = array_values(array_filter($events, static function (array $event) use ($moduleParam): bool {
                return strcasecmp($event['module'] ?? '', $moduleParam) === 0;
            }));
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['events' => $events], JSON_UNESCAPED_UNICODE);
    }

    private function parseMonth(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value . '-01');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
