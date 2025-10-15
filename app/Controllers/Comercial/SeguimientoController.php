<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\SeguimientoRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
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
        $pager = Pagination::fromRequest($filters, 1, 10, 0);
        $result = $this->repo->paginate($filters, $pager->page, $pager->perPage);

        view('comercial/seguimiento/index', [
            'layout'       => 'layout',
            'title'        => 'Seguimiento diario',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'filters'      => $filters,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/comercial', 'label' => 'Comercial'],
            ['href' => '/comercial/eventos', 'label' => 'Seguimiento diario'],
            ['label' => 'Nuevo seguimiento'],
        ]);

        view('comercial/seguimiento/create', [
            'layout'       => 'layout',
            'title'        => 'Nuevo seguimiento',
            'crumbs'       => $crumbs,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
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

        redirect('/comercial/eventos');
    }

    public function update(int $id): void
    {
        header('Content-Type: application/json; charset=UTF-8');

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

        $tipo = $data['tipo'];
        switch (mb_strtolower($tipo)) {
            case 'contacto':
                if ($data['id_contacto'] === null || $data['id_contacto'] <= 0) {
                    $errors[] = 'Debe seleccionar un contacto relacionado.';
                }
                $data['ticket_id'] = null;
                $ticketData = null;
                break;
            case 'ticket':
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
                $data['id_contacto'] = null;
                break;
            default:
                $data['id_contacto'] = null;
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
}
