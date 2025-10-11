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
        $departamentos = $this->repo->catalogoDepartamentos();
        $tiposPorDepartamento = $this->repo->catalogoTiposPorDepartamento();
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
            'departamentos' => $departamentos,
            'tiposPorDepartamento' => $tiposPorDepartamento,
            'prioridades'  => $this->repo->catalogoPrioridades(),
            'estados'      => $this->repo->catalogoEstados(),
        ]);
    }

    public function store(): void
    {
        $data = [
            'id_cooperativa'   => (int)($_POST['id_cooperativa'] ?? 0),
            'asunto'           => trim((string)($_POST['asunto'] ?? '')),
            'prioridad'        => trim((string)($_POST['prioridad'] ?? '')),
            'descripcion'      => trim((string)($_POST['descripcion'] ?? '')),
            'estado'           => 'Enviado',
            'creado_por'       => $this->currentUserId(),
        ];

        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $tipoId = (int)($_POST['tipo_incidencia_id'] ?? 0);
        $tipo = $this->repo->findTipoPorId($tipoId);

        if ($tipo === null && $departamentoId > 0) {
            $tipo = $this->repo->findPrimerTipoPorDepartamento($departamentoId);
        }

        if ($tipo !== null) {
            $departamentoId = $departamentoId > 0 ? $departamentoId : (int)$tipo['departamento_id'];
            if ((int)$tipo['departamento_id'] !== $departamentoId) {
                $departamentoId = (int)$tipo['departamento_id'];
            }
        }
        if (!in_array($data['prioridad'], $this->repo->catalogoPrioridades(), true)) {
            $data['prioridad'] = 'Medio';
        }

        if ($departamentoId < 1 || $tipo === null) {
            redirect('/comercial/incidencias');
            return;
        }

        $data['departamento_id'] = $departamentoId;
        $data['tipo_incidencia'] = $tipo['nombre'];
        $data['tipo_incidencia_id'] = (int)$tipo['id'];

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
            'prioridad'       => trim((string)($_POST['prioridad'] ?? '')),
            'estado'          => trim((string)($_POST['estado'] ?? '')),
            'descripcion'     => trim((string)($_POST['descripcion'] ?? '')),
        ];
        $departamentoId = (int)($_POST['departamento_id'] ?? 0);
        $tipoId = (int)($_POST['tipo_incidencia_id'] ?? 0);
        $tipo = $this->repo->findTipoPorId($tipoId);

        $detalle = $this->repo->findWithContacto($id);

        if ($departamentoId < 1 && isset($detalle['departamento_id'])) {
            $departamentoId = (int)$detalle['departamento_id'];
        }

        if ($tipo === null && $departamentoId > 0) {
            $tipo = $this->repo->findPrimerTipoPorDepartamento($departamentoId);
        }

        if ($tipo === null && isset($detalle['tipo_departamento_id'], $detalle['tipo_incidencia'])) {
            $tipo = [
                'id'              => (int)$detalle['tipo_departamento_id'],
                'departamento_id' => $departamentoId,
                'nombre'          => (string)$detalle['tipo_incidencia'],
            ];
        }

        if ($tipo !== null) {
            $departamentoId = $departamentoId > 0 ? $departamentoId : (int)$tipo['departamento_id'];
            if ((int)$tipo['departamento_id'] !== $departamentoId) {
                $departamentoId = (int)$tipo['departamento_id'];
            }
        }
      
        if (!in_array($data['prioridad'], $this->repo->catalogoPrioridades(), true)) {
            $data['prioridad'] = 'Medio';
        }

        if (!in_array($data['estado'], $this->repo->catalogoEstados(), true)) {
            $data['estado'] = 'Enviado';
        }

        if ($data['asunto'] === '') {
            $data['asunto'] = $detalle['asunto'] ?? 'Incidencia';
        }

        if ($tipo !== null) {
            $data['tipo_incidencia'] = $tipo['nombre'];
            $data['tipo_incidencia_id'] = (int)$tipo['id'];
        } else {
            $data['tipo_incidencia'] = isset($detalle['tipo_incidencia']) ? (string)$detalle['tipo_incidencia'] : 'Consulta operativa';
            if (isset($detalle['tipo_departamento_id'])) {
                $data['tipo_incidencia_id'] = (int)$detalle['tipo_departamento_id'];
            }
        }

        if ($departamentoId > 0) {
            $data['departamento_id'] = $departamentoId;
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
