<?php
declare(strict_types=1);
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\SeguimientoRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Support\Logger;
use RuntimeException;
use function redirect;
use function view;

final class SeguimientoController
{
    /** @var SeguimientoRepository */
    private $repo;

    public function __construct(?SeguimientoRepository $repo = null)
    {
        $this->repo = $repo ?? new SeguimientoRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $pager = Pagination::fromRequest($filters, 1, 6, 0);
        $grouped = $this->repo->agrupadoPorEntidad($filters, $pager->page, $pager->perPage);
        $toast = null;
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $toast = ['variant' => 'success', 'message' => 'Seguimiento registrado correctamente.'];
        } elseif (isset($_GET['updated']) && $_GET['updated'] === '1') {
            $toast = ['variant' => 'success', 'message' => 'Seguimiento actualizado correctamente.'];
        } elseif (isset($_GET['error']) && $_GET['error'] === 'validacion') {
            $toast = ['variant' => 'error', 'message' => 'Revisa la información ingresada antes de guardar.'];
        } elseif (isset($_GET['export_error']) && $_GET['export_error'] === 'filtros') {
            $toast = ['variant' => 'error', 'message' => 'Selecciona al menos una fecha, entidad o tipo de gestión antes de exportar.'];
        }

        view('comercial/seguimiento/index', [
            'layout'       => 'layout',
            'title'        => 'Seguimiento diario',
            'items'        => $grouped['items'],
            'total'        => $grouped['total'],
            'page'         => $grouped['page'],
            'perPage'      => $grouped['perPage'],
            'filters'      => $filters,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
            'toast'        => $toast,
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/comercial', 'label' => 'Comercial'],
            ['href' => '/comercial/eventos', 'label' => 'Seguimiento diario'],
            ['label' => 'Nuevo seguimiento'],
        ]);
        $toast = null;
        if (isset($_GET['error']) && $_GET['error'] === 'validacion') {
            $toast = ['variant' => 'error', 'message' => 'No se pudo guardar. Verifica la información ingresada.'];
        }

        view('comercial/seguimiento/create', [
            'layout'       => 'layout',
            'title'        => 'Nuevo seguimiento',
            'crumbs'       => $crumbs,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
            'toast'        => $toast,
        ]);
    }

    public function store(): void
    {
        $parsed = $this->parseSeguimientoInput($_POST, false);
        if ($parsed['errors']) {
            redirect('/comercial/eventos/crear?error=validacion');
            return;
        }

        $data = $parsed['data'];
        $data['creado_por'] = $this->currentUserId();
        $this->repo->create($data);

        redirect('/comercial/eventos?created=1');
    }

    public function update($id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode([
                'ok' => false,
                'errors' => ['Seguimiento inválido.'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $parsed = $this->parseSeguimientoInput($_POST, true);
        if ($parsed['errors']) {
            http_response_code(422);
            echo json_encode([
                'ok'     => false,
                'errors' => $parsed['errors'],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $data = $parsed['data'];
        $data['usuario_editor'] = $this->currentUserId();
        $this->repo->update($id, $data);

        $fresh = $this->repo->find($id);
        echo json_encode([
            'ok'   => true,
            'item' => $fresh,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function export(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        if (!$this->hasExportFilters($filters)) {
            redirect('/comercial/eventos?export_error=filtros');
            return;
        }
        $rows = $this->repo->listarParaExportar($filters);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seguimiento-' . date('Ymd') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) {
            redirect('/comercial/eventos');
            return;
        }

        fputcsv($out, ['Fecha inicio', 'Fecha fin', 'Entidad', 'Tipo', 'Descripción', 'Ticket', 'Registrado por'], ';');
        foreach ($rows as $row) {
            $descripcion = isset($row['descripcion']) ? preg_replace('/\s+/u', ' ', (string)$row['descripcion']) : '';
            fputcsv($out, [
                isset($row['fecha_inicio']) ? (string)$row['fecha_inicio'] : '',
                isset($row['fecha_fin']) ? (string)$row['fecha_fin'] : '',
                isset($row['cooperativa']) ? (string)$row['cooperativa'] : '',
                isset($row['tipo']) ? (string)$row['tipo'] : '',
                $descripcion,
                isset($row['ticket_codigo']) ? (string)$row['ticket_codigo'] : '',
                isset($row['usuario']) ? (string)$row['usuario'] : '',
            ], ';');
        }

        fclose($out);
        exit;
    }

    public function contactos(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $entidadId = isset($_GET['entidad']) ? (int)$_GET['entidad'] : 0;
        if ($entidadId <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'errors' => ['Seleccione una entidad válida.']], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = $this->repo->contactosPorEntidad($entidadId);
        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function ticketSearch(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $term = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($term === '') {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = $this->repo->buscarTickets($term);
        $items = array_map(static function ($row) {
            if (!isset($row['id']) && isset($row['id_ticket'])) {
                $row['id'] = (int) $row['id_ticket'];
            }
            if (!isset($row['id_ticket']) && isset($row['id'])) {
                $row['id_ticket'] = (int) $row['id'];
            }
            return $row;
        }, $items);

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function ticketFilterSearch(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $term = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($term === '') {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = $this->repo->buscarTicketsSeguimiento($term);
        $items = array_map(static function ($row) {
            if (!isset($row['id']) && isset($row['ticket_id'])) {
                $row['id'] = (int) $row['ticket_id'];
            }
            if (!isset($row['ticket_id']) && isset($row['id'])) {
                $row['ticket_id'] = (int) $row['id'];
            }
            return $row;
        }, $items);

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function ticketInfo(int $id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $ticket = $this->repo->ticketPorId($id);
        if ($ticket === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'errors' => ['Ticket no encontrado.']], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'item' => $ticket], JSON_UNESCAPED_UNICODE);
    }

    public function entityHistory($id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $entityId = (int)$id;
        if ($entityId < 1) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'errors' => ['Entidad inválida.']], JSON_UNESCAPED_UNICODE);
            return;
        }

        $filters = is_array($_GET) ? $_GET : [];
        $filters['coop'] = (string)$entityId;

        try {
            $rows = $this->repo->listarParaExportar($filters);
        } catch (\Throwable $e) {
            Logger::error($e, 'SeguimientoController::entityHistory');
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => ['No se pudo obtener el historial.']], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = array_map(static function (array $row): array {
            return [
                'id'          => isset($row['id']) ? (int)$row['id'] : 0,
                'tipo'        => isset($row['tipo']) ? (string)$row['tipo'] : '',
                'descripcion' => isset($row['descripcion']) ? (string)$row['descripcion'] : '',
                'fecha'       => isset($row['fecha_inicio']) ? (string)$row['fecha_inicio'] : ($row['fecha_actividad'] ?? ''),
                'fecha_fin'   => isset($row['fecha_fin']) ? (string)$row['fecha_fin'] : ($row['fecha_finalizacion'] ?? ''),
                'ticket'      => isset($row['ticket_codigo']) ? (string)$row['ticket_codigo'] : ($row['ticket_id'] ?? null),
                'usuario'     => isset($row['usuario']) ? (string)$row['usuario'] : '',
            ];
        }, $rows);

        echo json_encode([
            'ok'    => true,
            'items' => $items,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function delete($id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'errors' => ['Seguimiento inválido.']], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $this->repo->delete($id);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => [$e->getMessage()]], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string,mixed> $source
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private function parseSeguimientoInput(array $source, bool $forUpdate): array
    {
        $data = [
            'id_cooperativa' => (int)($source['id_cooperativa'] ?? 0),
            'fecha_inicio'   => trim((string)($source['fecha_inicio'] ?? '')),
            'fecha_fin'      => trim((string)($source['fecha_fin'] ?? '')),
            'tipo'           => trim((string)($source['tipo'] ?? '')),
            'descripcion'    => trim((string)($source['descripcion'] ?? '')),
            'id_contacto'    => isset($source['id_contacto']) && $source['id_contacto'] !== ''
                ? (int)$source['id_contacto']
                : null,
            'ticket_id'      => isset($source['ticket_id']) && $source['ticket_id'] !== ''
                ? (int)$source['ticket_id']
                : null,
            'datos_ticket'   => isset($source['ticket_datos']) ? trim((string)$source['ticket_datos']) : '',
            'datos_reunion'  => null,
        ];

        if ($data['fecha_fin'] === '') {
            $data['fecha_fin'] = null;
        }

        $errors = [];
        if ($data['id_cooperativa'] <= 0) {
            $errors[] = 'Debe seleccionar una entidad válida.';
        }
        if ($data['fecha_inicio'] === '') {
            $errors[] = 'Debe indicar la fecha de inicio.';
        }
        if ($data['tipo'] === '') {
            $errors[] = 'Debe seleccionar el tipo de gestión.';
        }
        if ($data['descripcion'] === '') {
            $errors[] = 'La descripción es obligatoria.';
        }

        if ($data['fecha_inicio'] !== null && $data['fecha_fin'] !== null && $data['fecha_fin'] !== '') {
            if (strtotime($data['fecha_fin']) !== false && strtotime($data['fecha_inicio']) !== false) {
                if (strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio'])) {
                    $errors[] = 'La fecha de finalización no puede ser anterior a la fecha de inicio.';
                }
            }
        }

        $ticketData = null;
        if ($data['datos_ticket'] !== '') {
            $decoded = json_decode($data['datos_ticket'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ticketData = $decoded;
            } else {
                $errors[] = 'El detalle del ticket es inválido.';
            }
        }

        $normalizedTipo = $this->normalizeTypeValue($data['tipo']);
        $visibleContactTypes = ['contacto', 'llamada', 'reunion', 'visita', 'soporte', 'ticket'];
        $contactRequiredTypes = ['contacto', 'llamada', 'reunion', 'visita', 'soporte'];
        $requiresTicket = in_array($normalizedTipo, ['ticket', 'soporte'], true);

        $showContact = in_array($normalizedTipo, $visibleContactTypes, true);
        $requiresContact = in_array($normalizedTipo, $contactRequiredTypes, true);

        if (!$showContact) {
            $data['id_contacto'] = null;
        } elseif ($data['id_contacto'] !== null && $data['id_contacto'] <= 0) {
            $data['id_contacto'] = null;
        }

        if ($requiresContact) {
            if ($data['id_contacto'] === null || $data['id_contacto'] <= 0) {
                $errors[] = 'Debe seleccionar un contacto relacionado.';
            }
        }

        if ($requiresTicket) {
            if ($data['ticket_id'] === null || $data['ticket_id'] <= 0) {
                $errors[] = 'Debe seleccionar un ticket.';
            }
            if ($ticketData === null && $data['ticket_id']) {
                try {
                    $ticket = $this->repo->ticketPorId($data['ticket_id']);
                } catch (RuntimeException $e) {
                    $ticket = null;
                }
                if ($ticket) {
                    $ticketData = [
                        'codigo'       => $ticket['codigo'] ?? '',
                        'departamento' => $ticket['departamento'] ?? '',
                        'tipo'         => $ticket['tipo'] ?? '',
                        'prioridad'    => $ticket['prioridad'] ?? '',
                        'estado'       => $ticket['estado'] ?? '',
                    ];
                }
            }
        } else {
            $data['ticket_id'] = null;
            $ticketData = null;
        }

        $data['datos_ticket'] = $ticketData !== null
            ? json_encode($ticketData, JSON_UNESCAPED_UNICODE)
            : null;

        return [
            'data'   => $data,
            'errors' => $errors,
        ];
    }

    private function currentUserId(): ?int
    {
        if (!empty($_SESSION['auth']['id'])) {
            return (int)$_SESSION['auth']['id'];
        }
        return null;
    }

    private function normalizeTypeValue(?string $value): string
    {
        if (!is_string($value)) {
            return '';
        }
        $normalized = trim(mb_strtolower($value));
        if ($normalized === '') {
            return '';
        }
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ];
        return strtr($normalized, $map);
    }

    private function hasExportFilters(array $filters): bool
    {
        $keys = ['fecha', 'desde', 'hasta', 'coop', 'tipo', 'q', 'ticket'];
        foreach ($keys as $key) {
            if (!isset($filters[$key])) {
                continue;
            }
            $value = $filters[$key];
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (trim((string)$item) !== '') {
                        return true;
                    }
                }
            } elseif (trim((string)$value) !== '') {
                return true;
            }
        }
        return false;
    }
}
