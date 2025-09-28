<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\ValidationService;
use App\Services\Shared\UbicacionesService;
use App\Support\Logger;
use function \view;
use function \redirect;

final class EntidadesController
{
    private BuscarEntidadesService $buscarEntidades;
    private EntidadRepository $entidades;
    private ValidationService $validator;
    private UbicacionesService $ubicaciones;

    public function __construct(
        ?BuscarEntidadesService $buscarEntidades = null,
        ?EntidadRepository $entidades = null,
        ?ValidationService $validator = null,
        ?UbicacionesService $ubicaciones = null
    ) {
        $this->entidades       = $entidades ?? new EntidadRepository();
        $this->validator       = $validator ?? new ValidationService();
        $this->ubicaciones     = $ubicaciones ?? new UbicacionesService();
        $this->buscarEntidades = $buscarEntidades ?? new BuscarEntidadesService($this->entidades, $this->validator);
    }

    public function index()
    {
        $filters = is_array($_GET) ? $_GET : [];
        $q       = trim((string)($filters['q'] ?? ''));
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->buscarEntidades->buscar($q, $pager->page, $pager->perPage);

        $toastMessage = null;
        if (isset($_GET['created']) && $_GET['created'] === '1') {
            $toastMessage = 'Entidad creada correctamente';
        } elseif (isset($_GET['ok']) && $_GET['ok'] === '1') {
            $toastMessage = 'Cambios guardados';
        }

        return view('comercial/entidades/index', [
            'layout'  => 'layout',
            'title'   => 'Entidades financieras',
            'items'   => $result['items'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'perPage' => $result['perPage'],
            'q'       => $q,
            'filters' => $filters,
            'toastMessage' => $toastMessage,
        ]);
    }
    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->showJson($id);
    }

    public function showJson($id): void
    {
        $id = (int) $id;

        if ($id < 1) {
            $this->respondEntidadJson(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            $data = $this->loadEntidadDetalle($id);
            if ($data === null) {
                $this->respondEntidadJson(['error' => 'No se pudo obtener la entidad'], 404);
                return;
            }

            $this->respondEntidadJson(['data' => $data], 200);
            return;
        } catch (\Throwable $e) {
            $this->respondEntidadJson(['error' => 'Error interno'], 500);
            return;
        }
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href'=>'/comercial', 'label'=>'Comercial'],
            ['href'=>'/comercial/entidades', 'label'=>'Entidades'],
            ['label'=>'Crear']
        ]);

        $provincias = $this->ubicaciones->provincias();
        $repo        = $this->entidades;

        view('comercial/entidades/create', [
            'title'      => 'Nueva Entidad',
            'crumbs'     => $crumbs,
            'provincias' => $provincias, // <<<<<
            'action'     => '/comercial/entidades',
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
        ]);
    }

    public function create(): void
    {
        $repo = $this->entidades;
        $res  = $this->validator->validarEntidad($_POST);

        if (!$res['ok']) {
            http_response_code(400);
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Crear']];
            view('comercial/entidades/create', [
                'title'=>'Nueva Cooperativa',
                'crumbs'=>$crumbs,
                'provincias'=>$this->ubicaciones->provincias(),
                'cantones'=>$this->ubicaciones->cantones((int)($res['data']['provincia_id'] ?? 0)),
                'segmentos'=>$repo->segmentos(),
                'servicios'=>$repo->servicios(),
                'tipos'=>['cooperativa','mutualista','sujeto obligado no financiero','caja de ahorros','casa de valores'],
                'errors'=>$res['errors'],
                'old'=>$res['data'],
                'action'=>'/comercial/entidades',
            ]);
            return;
        }

        try {
            $newId = $repo->create($res['data']);
        } catch (\Throwable $e) {
            Logger::error($e, 'EntidadesController::create');
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            return;
        }
        redirect('/comercial/entidades/editar?id=' . $newId . '&created=1');
    }

    public function editForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) { redirect('/comercial/entidades'); }

        $repo = $this->entidades;
        $row  = $repo->findById($id);
        if (!$row) { redirect('/comercial/entidades'); }

        $row['id'] = (int)($row['id'] ?? $row['id_cooperativa'] ?? $id);
        $row['id_entidad'] = (int)($row['id_entidad'] ?? $row['id'] ?? $id);
        $row['id_cooperativa'] = (int)($row['id_cooperativa'] ?? $row['id'] ?? $id);

        $crumbs = Breadcrumbs::make([
            ['href'=>'/comercial', 'label'=>'Comercial'],
            ['href'=>'/comercial/entidades', 'label'=>'Entidades'],
            ['label'=>'Editar']
        ]);

        $provincias = $this->ubicaciones->provincias();
        $cantones   = $this->ubicaciones->cantones((int)($row['provincia_id'] ?? 0));

        view('comercial/entidades/edit', [
            'title'      => 'Editar Entidad',
            'crumbs'     => $crumbs,
            'item'       => $row,
            'provincias' => $provincias, // <<<<<
            'action'     => '/comercial/entidades/' . $id,
            'cantones'   => $cantones,
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
        ]);
    }

    public function update($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            $id = (int)($_POST['id'] ?? 0);
        }
        if ($id < 1) { redirect('/comercial/entidades'); return; }

        $repo = $this->entidades;
        $res  = $this->validator->validarEntidad($_POST);

        if (!$res['ok']) {
            http_response_code(400);
            $row = array_merge((array)$repo->findById($id), $res['data']);
            $row['id'] = $id;
            $row['id_entidad'] = $id;
            $row['id_cooperativa'] = $id;
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Editar']];
            view('comercial/entidades/edit', [
                'title'=>'Editar Cooperativa',
                'crumbs'=>$crumbs,
                'item'=>$row,
                'provincias'=>$this->ubicaciones->provincias(),
                'cantones'=>$this->ubicaciones->cantones((int)($row['provincia_id'] ?? $res['data']['provincia_id'] ?? 0)),
                'segmentos'=>$repo->segmentos(),
                'servicios'=>$repo->servicios(),
                'tipos'=>['cooperativa','mutualista','sujeto obligado no financiero','caja de ahorros','casa de valores'],
                'errors'=>$res['errors'],
                'sel'=>array_map('intval',(array)($_POST['servicios'] ?? [])),
                'action'=>'/comercial/entidades/' . $id,
            ]);
            return;
        }

        try {
            $repo->update($id, $res['data']);
            $repo->replaceServicios($id, $res['data']['servicios'] ?? []);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'No se pudo actualizar la entidad';
            return;
        }

        redirect('/comercial/entidades?ok=1');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { $this->entidades->delete($id); }
        redirect('/comercial/entidades');
    }

    // --- Helpers JSON (privados) ---
    private function respondEntidadJson(array $payload, $status = 200): void
    {
        http_response_code((int) $status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function loadEntidadDetalle(int $id): ?array
    {
        $repo = $this->entidades;
        $row  = $repo->findDetalles($id);
        if (!$row) {
            return null;
        }

        $servicios = $repo->serviciosActivos($id);

        $provincia = trim((string)($row['provincia'] ?? ''));
        $canton    = trim((string)($row['canton'] ?? ''));
        $ubicacion = trim($provincia . (($provincia !== '' && $canton !== '') ? ' - ' : '') . $canton);
        if ($ubicacion === '') {
            $ubicacion = 'No especificado';
        }

        $segmentoNombre = trim((string)($row['segmento_nombre'] ?? ''));
        if ($segmentoNombre === '' && !empty($row['id_segmento'])) {
            $segmentoNombre = 'Segmento ' . (int)$row['id_segmento'];
        }
        if ($segmentoNombre === '') {
            $segmentoNombre = 'No especificado';
        }

        return [
            'nombre'         => $row['nombre'],
            'ruc'            => $row['ruc'] ?? null,
            'telefono_fijo'  => $row['telefono_fijo_1'] ?? null,
            'telefono_movil' => $row['telefono_movil'] ?? null,
            'email'          => $row['email'] ?? null,
            'tipo'           => $row['tipo_entidad'] ?? null,
            'segmento'       => $segmentoNombre,
            'ubicacion'      => $ubicacion,
            'notas'          => $row['notas'] ?? null,
            'servicios'      => $servicios,
        ];
    }
}
