<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Repositories\Contabilidad\SeguimientoRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use RuntimeException;
use function redirect;
use function view;

final class SeguimientoController
{
    private SeguimientoRepository $repo;

    public function __construct(?SeguimientoRepository $repo = null)
    {
        $this->repo = $repo ?? new SeguimientoRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->repo->paginate($filters, $pager->page, $pager->perPage);

        $toast = null;
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $toast = ['variant' => 'success', 'message' => 'Gestión registrada correctamente.'];
        }

        view('contabilidad/seguimiento/index', [
            'layout'       => 'layout',
            'title'        => 'Seguimiento contable',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'filters'      => $filters,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
            'medios'       => $this->repo->catalogoMedios(),
            'resultados'   => $this->repo->catalogoResultados(),
            'toast'        => $toast,
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/seguimiento', 'label' => 'Seguimiento'],
            ['label' => 'Registrar gestión'],
        ]);

        view('contabilidad/seguimiento/create', [
            'layout'       => 'layout',
            'title'        => 'Registrar gestión contable',
            'crumbs'       => $crumbs,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoTipos(),
            'medios'       => $this->repo->catalogoMedios(),
            'resultados'   => $this->repo->catalogoResultados(),
            'action'       => '/contabilidad/seguimiento',
        ]);
    }

    public function store(): void
    {
        $parsed = $this->parseInput($_POST);
        if ($parsed['errors']) {
            redirect('/contabilidad/seguimiento/crear?error=validacion');
            return;
        }

        $data = $parsed['data'];
        $data['creado_por'] = $this->currentUserId();

        try {
            $this->repo->create($data);
        } catch (RuntimeException $e) {
            redirect('/contabilidad/seguimiento/crear?error=validacion');
            return;
        }

        redirect('/contabilidad/seguimiento?created=1');
    }

    public function contactos(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $entidad = isset($_GET['entidad']) ? (int)$_GET['entidad'] : 0;
        if ($entidad <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'errors' => ['Entidad inválida']], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $items = $this->repo->contactosPorEntidad($entidad);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => [$e->getMessage()]], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function contratos(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $entidad = isset($_GET['entidad']) ? (int)$_GET['entidad'] : 0;
        if ($entidad <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'errors' => ['Entidad inválida']], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $items = $this->repo->contratosPorEntidad($entidad);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => [$e->getMessage()]], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function ticketSearch(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($q === '') {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $items = $this->repo->buscarTickets($q);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => [$e->getMessage()]], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function ticketInfo(int $id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $ticket = $this->repo->ticketPorId($id);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => [$e->getMessage()]], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($ticket === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'errors' => ['Ticket no encontrado']], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'item' => $ticket], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string,mixed> $source
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private function parseInput(array $source): array
    {
        $data = [
            'id_cooperativa'  => (int)($source['id_cooperativa'] ?? 0),
            'id_contratacion' => isset($source['id_contratacion']) && $source['id_contratacion'] !== ''
                ? (int)$source['id_contratacion']
                : null,
            'fecha_inicio'    => trim((string)($source['fecha_inicio'] ?? '')),
            'fecha_fin'       => trim((string)($source['fecha_fin'] ?? '')),
            'tipo'            => trim((string)($source['tipo'] ?? '')),
            'medio'           => trim((string)($source['medio'] ?? '')),
            'resultado'       => trim((string)($source['resultado'] ?? '')),
            'descripcion'     => trim((string)($source['descripcion'] ?? '')),
            'id_contacto'     => isset($source['id_contacto']) && $source['id_contacto'] !== ''
                ? (int)$source['id_contacto']
                : null,
            'ticket_id'       => isset($source['ticket_id']) && $source['ticket_id'] !== ''
                ? (int)$source['ticket_id']
                : null,
            'datos_ticket'    => null,
            'datos_reunion'   => null,
        ];

        if ($data['fecha_fin'] === '') {
            $data['fecha_fin'] = null;
        }

        $errors = [];
        if ($data['id_cooperativa'] <= 0) {
            $errors[] = 'Selecciona la entidad.';
        }
        if ($data['fecha_inicio'] === '') {
            $errors[] = 'Define la fecha de gestión.';
        }
        if ($data['tipo'] === '') {
            $errors[] = 'Selecciona el tipo de gestión.';
        }
        if ($data['descripcion'] === '') {
            $errors[] = 'Ingresa el detalle de la gestión.';
        }

        if ($data['fecha_inicio'] !== '' && $data['fecha_fin'] !== null && $data['fecha_fin'] !== '') {
            $ini = strtotime($data['fecha_inicio']);
            $fin = strtotime($data['fecha_fin']);
            if ($ini !== false && $fin !== false && $fin < $ini) {
                $errors[] = 'La fecha de cierre no puede ser anterior al inicio.';
            }
        }

        $normalizedTipo = $this->normalizeType($data['tipo']);
        $contactRequired = in_array($normalizedTipo, ['conciliacion', 'cobranza', 'reunion', 'visita'], true);
        if ($contactRequired && ($data['id_contacto'] === null || $data['id_contacto'] <= 0)) {
            $errors[] = 'Selecciona el contacto relacionado.';
        }

        $ticketRequired = in_array($normalizedTipo, ['soporte', 'ticket'], true);
        if ($ticketRequired && ($data['ticket_id'] === null || $data['ticket_id'] <= 0)) {
            $errors[] = 'Selecciona el ticket asociado.';
        }

        if ($data['ticket_id']) {
            try {
                $ticket = $this->repo->ticketPorId($data['ticket_id']);
            } catch (RuntimeException $e) {
                $ticket = null;
            }
            if ($ticket) {
                $data['datos_ticket'] = json_encode($ticket, JSON_UNESCAPED_UNICODE);
            }
        }

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

    private function normalizeType(string $value): string
    {
        $normalized = mb_strtolower(trim($value));
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
}
