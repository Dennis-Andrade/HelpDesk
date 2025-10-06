<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\IncidenciaRepository;
use App\Services\Shared\Pagination;
use function \redirect;
use function \view;

final class IncidenciasController
{
    /** @var IncidenciaRepository */
    private $repo;

    public function __construct(?IncidenciaRepository $repo = null)
    {
        $this->repo = $repo ?? new IncidenciaRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);

        $result = $this->repo->paginate($filters, $pager->page, $pager->perPage);

        view('comercial/incidencias/index', [
            'layout'       => 'layout',
            'title'        => 'Incidencias para sistemas',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'filters'      => $filters,
            'cooperativas' => $this->repo->listadoCooperativas(),
            'tipos'        => $this->repo->catalogoIncidencias(),
            'prioridades'  => $this->repo->catalogoPrioridades(),
            'estados'      => $this->repo->catalogoEstados(),
        ]);
    }

    public function store(): void
    {
        $data = [
            'id_cooperativa'   => (int)($_POST['id_cooperativa'] ?? 0),
            'asunto'           => trim((string)($_POST['asunto'] ?? '')),
            'tipo_incidencia'  => trim((string)($_POST['tipo_incidencia'] ?? '')),
            'prioridad'        => trim((string)($_POST['prioridad'] ?? '')),
            'descripcion'      => trim((string)($_POST['descripcion'] ?? '')),
            'estado'           => 'Enviado',
            'creado_por'       => $this->currentUserId(),
        ];

        if (!in_array($data['prioridad'], $this->repo->catalogoPrioridades(), true)) {
            $data['prioridad'] = 'Medio';
        }

        if (!in_array($data['tipo_incidencia'], $this->repo->catalogoIncidencias(), true)) {
            $data['tipo_incidencia'] = 'Consulta operativa';
        }

        if ($data['asunto'] === '' || $data['id_cooperativa'] < 1) {
            redirect('/comercial/incidencias');
            return;
        }

        $this->repo->create($data);
        redirect('/comercial/incidencias');
    }

    public function update($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/comercial/incidencias');
            return;
        }

        $data = [
            'asunto'          => trim((string)($_POST['asunto'] ?? '')),
            'tipo_incidencia' => trim((string)($_POST['tipo_incidencia'] ?? '')),
            'prioridad'       => trim((string)($_POST['prioridad'] ?? '')),
            'estado'          => trim((string)($_POST['estado'] ?? '')),
            'descripcion'     => trim((string)($_POST['descripcion'] ?? '')),
        ];

        if (!in_array($data['prioridad'], $this->repo->catalogoPrioridades(), true)) {
            $data['prioridad'] = 'Medio';
        }

        if (!in_array($data['estado'], $this->repo->catalogoEstados(), true)) {
            $data['estado'] = 'Enviado';
        }

        if (!in_array($data['tipo_incidencia'], $this->repo->catalogoIncidencias(), true)) {
            $data['tipo_incidencia'] = 'Consulta operativa';
        }

        if ($data['asunto'] === '') {
            $detalle = $this->repo->findWithContacto($id);
            $data['asunto'] = $detalle['asunto'] ?? 'Incidencia';
        }

        $this->repo->update($id, $data);
        redirect('/comercial/incidencias');
    }

    public function delete($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            $id = (int)($_POST['id'] ?? 0);
        }
        if ($id > 0) {
            $this->repo->delete($id);
        }
        redirect('/comercial/incidencias');
    }

    private function currentUserId(): ?int
    {
        if (!empty($_SESSION['auth']['id'])) {
            return (int)$_SESSION['auth']['id'];
        }
        return null;
    }
}
