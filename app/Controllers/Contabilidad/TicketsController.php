<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Repositories\Contabilidad\TicketRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use RuntimeException;
use function redirect;
use function view;

final class TicketsController
{
    private TicketRepository $repo;

    public function __construct(?TicketRepository $repo = null)
    {
        $this->repo = $repo ?? new TicketRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->repo->paginate($filters, $pager->page, $pager->perPage);

        view('contabilidad/tickets/index', [
            'layout'       => 'layout',
            'title'        => 'Solicitudes contables',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'filters'      => $filters,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'prioridades'  => $this->repo->catalogoPrioridades(),
            'estados'      => $this->repo->catalogoEstados(),
            'categorias'   => $this->repo->catalogoCategorias(),
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/tickets', 'label' => 'Solicitudes contables'],
            ['label' => 'Nuevo ticket'],
        ]);

        view('contabilidad/tickets/create', [
            'layout'       => 'layout',
            'title'        => 'Nuevo ticket contable',
            'crumbs'       => $crumbs,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'prioridades'  => $this->repo->catalogoPrioridades(),
            'estados'      => $this->repo->catalogoEstados(),
            'categorias'   => $this->repo->catalogoCategorias(),
            'action'       => '/contabilidad/tickets',
        ]);
    }

    public function store(): void
    {
        $parsed = $this->parseInput($_POST);
        if ($parsed['errors']) {
            redirect('/contabilidad/tickets/crear?error=validacion');
            return;
        }

        $data = $parsed['data'];
        $data['creado_por'] = $this->currentUserId();

        try {
            $this->repo->create($data);
        } catch (RuntimeException $e) {
            redirect('/contabilidad/tickets/crear?error=validacion');
            return;
        }

        redirect('/contabilidad/tickets');
    }

    public function editForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            redirect('/contabilidad/tickets');
            return;
        }

        $ticket = $this->repo->find($id);
        if ($ticket === null) {
            redirect('/contabilidad/tickets');
            return;
        }

        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/tickets', 'label' => 'Solicitudes contables'],
            ['label' => 'Editar ticket'],
        ]);

        view('contabilidad/tickets/edit', [
            'layout'       => 'layout',
            'title'        => 'Editar ticket contable',
            'crumbs'       => $crumbs,
            'item'         => $ticket,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'prioridades'  => $this->repo->catalogoPrioridades(),
            'estados'      => $this->repo->catalogoEstados(),
            'categorias'   => $this->repo->catalogoCategorias(),
            'action'       => '/contabilidad/tickets/' . $id,
        ]);
    }

    public function update($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/contabilidad/tickets');
            return;
        }

        $parsed = $this->parseInput($_POST);
        if ($parsed['errors']) {
            redirect('/contabilidad/tickets/editar?id=' . $id . '&error=validacion');
            return;
        }

        $data = $parsed['data'];
        $data['creado_por'] = $this->currentUserId();

        try {
            $this->repo->update($id, $data);
        } catch (RuntimeException $e) {
            redirect('/contabilidad/tickets/editar?id=' . $id . '&error=validacion');
            return;
        }

        redirect('/contabilidad/tickets');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            redirect('/contabilidad/tickets');
            return;
        }

        try {
            $this->repo->delete($id);
        } catch (RuntimeException $e) {
            redirect('/contabilidad/tickets');
            return;
        }

        redirect('/contabilidad/tickets');
    }

    /**
     * @param array<string,mixed> $input
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private function parseInput(array $input): array
    {
        $data = [
            'id_cooperativa'  => (int)($input['id_cooperativa'] ?? 0),
            'id_contratacion' => isset($input['id_contratacion']) && $input['id_contratacion'] !== ''
                ? (int)$input['id_contratacion']
                : null,
            'asunto'          => trim((string)($input['asunto'] ?? '')),
            'categoria'       => trim((string)($input['categoria'] ?? '')),
            'prioridad'       => trim((string)($input['prioridad'] ?? '')),
            'estado'          => trim((string)($input['estado'] ?? '')),
            'descripcion'     => trim((string)($input['descripcion'] ?? '')),
            'observaciones'   => trim((string)($input['observaciones'] ?? '')),
        ];

        $errors = [];
        if ($data['id_cooperativa'] <= 0) {
            $errors[] = 'Selecciona la entidad.';
        }
        if ($data['asunto'] === '') {
            $errors[] = 'El asunto es obligatorio.';
        }
        if ($data['categoria'] === '') {
            $errors[] = 'Selecciona la categoría del ticket.';
        }
        if ($data['prioridad'] === '') {
            $errors[] = 'Selecciona la prioridad.';
        }
        if ($data['estado'] === '') {
            $errors[] = 'Selecciona el estado.';
        }
        if ($data['descripcion'] === '') {
            $errors[] = 'Describe el ticket.';
        }

        if (!in_array($data['prioridad'], $this->repo->catalogoPrioridades(), true)) {
            $errors[] = 'Prioridad inválida.';
        }
        if (!in_array($data['estado'], $this->repo->catalogoEstados(), true)) {
            $errors[] = 'Estado inválido.';
        }
        if (!in_array($data['categoria'], $this->repo->catalogoCategorias(), true)) {
            $errors[] = 'Categoría inválida.';
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
}
