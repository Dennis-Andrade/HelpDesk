<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\ValidationService;
use App\Services\Shared\UbicacionesService;
use App\Support\Logger;
use PDOException;
use function Config\db;
use function \view;
use function \redirect;
use function \csrf_token;
use function \csrf_verify;

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
        $repo                  = $entidades ?? new EntidadRepository(db());
        $this->entidades       = $repo;
        $this->validator       = $validator ?? new ValidationService();
        $this->ubicaciones     = $ubicaciones ?? new UbicacionesService();
        $this->buscarEntidades = $buscarEntidades ?? new BuscarEntidadesService($repo, $this->validator);
    }

    private function strOrNull($v)
    {
        $v = trim((string)$v);
        return $v === '' ? null : $v;
    }

    private function intOrNull($v)
    {
        $v = trim((string)$v);
        return $v === '' ? null : (int)$v;
    }

    private function digitsOrNull($v)
    {
        $v = trim((string)$v);
        if ($v === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $v);
        return $digits === '' ? null : $digits;
    }

    private function intsFromArray($a)
    {
        return array_values(array_unique(array_map('intval', (array)$a)));
    }

    private function makeDto(array $in): array
    {
        $tipo = $in['tipo_entidad'] ?? 'cooperativa';
        $dto  = [
            'id'              => null,
            'nombre'          => trim((string)($in['nombre'] ?? '')),
            'ruc'             => isset($in['ruc']) ? $this->digitsOrNull($in['ruc'])
                                  : (isset($in['nit']) ? $this->digitsOrNull($in['nit']) : null),
            'telefono'        => null,
            'telefono_fijo_1' => isset($in['telefono_fijo_1']) ? $this->digitsOrNull($in['telefono_fijo_1'])
                                  : (isset($in['telefono_fijo']) ? $this->digitsOrNull($in['telefono_fijo']) : null),
            'telefono_movil'  => $this->digitsOrNull($in['telefono_movil'] ?? ''),
            'email'           => $this->strOrNull($in['email'] ?? ''),
            'provincia_id'    => $this->intOrNull($in['provincia_id'] ?? ''),
            'canton_id'       => $this->intOrNull($in['canton_id'] ?? ''),
            'tipo_entidad'    => $this->strOrNull($tipo),
            'id_segmento'     => $tipo === 'cooperativa'
                                   ? $this->intOrNull($in['id_segmento'] ?? '')
                                   : null,
            'notas'           => $this->strOrNull($in['notas'] ?? ''),
            'servicios'       => $this->intsFromArray($in['servicios'] ?? []),
        ];
        if (in_array(1, $dto['servicios'], true)) {
            $dto['servicios'] = [1];
        }
        return $dto;
    }

    /**
     * @return array{errors:array<string,string>, data:array<string,mixed>}
     */
    private function validateDto(array $dto): array
    {
        $errors = [];

        if ($dto['nombre'] === '') {
            $errors['nombre'] = 'El nombre es obligatorio';
        }

        if ($dto['email'] !== null && !filter_var($dto['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if ($dto['ruc'] !== null) {
            $len = strlen($dto['ruc']);
            if ($len < 10 || $len > 13) {
                $errors['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
            }
        }

        if ($dto['telefono_fijo_1'] !== null && strlen($dto['telefono_fijo_1']) !== 7) {
            $errors['telefono_fijo_1'] = 'El teléfono fijo debe tener 7 dígitos';
        }

        if ($dto['telefono_movil'] !== null && strlen($dto['telefono_movil']) !== 10) {
            $errors['telefono_movil'] = 'El celular debe tener 10 dígitos';
        }

        $permitidos = ['cooperativa','mutualista','sujeto_no_financiero','caja_ahorros','casa_valores'];
        if ($dto['tipo_entidad'] === null || !in_array($dto['tipo_entidad'], $permitidos, true)) {
            $errors['tipo_entidad'] = 'Tipo de entidad inválido';
        }

        if ($dto['tipo_entidad'] !== 'cooperativa') {
            $dto['id_segmento'] = null;
        } elseif ($dto['id_segmento'] === null) {
            $errors['id_segmento'] = 'Debe seleccionar un segmento';
        }

        return ['errors' => $errors, 'data' => $dto];
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
            'csrf'    => csrf_token(),
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
            'csrf'       => csrf_token(),
            'provincias' => $provincias, // <<<<<
            'action'     => '/comercial/entidades',
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
        ]);
    }

    public function create(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) { http_response_code(400); echo 'CSRF inválido'; return; }

        $dto    = $this->makeDto($_POST);
        $result = $this->validateDto($dto);
        if ($result['errors'] !== []) {
            http_response_code(400);
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Crear']];
            $repo   = $this->entidades;
            view('comercial/entidades/create', [
                'title'      => 'Nueva Cooperativa',
                'crumbs'     => $crumbs,
                'csrf'       => csrf_token(),
                'provincias' => $this->ubicaciones->provincias(),
                'cantones'   => $this->ubicaciones->cantones((int)($dto['provincia_id'] ?? 0)),
                'segmentos'  => $repo->segmentos(),
                'servicios'  => $repo->servicios(),
                'errors'     => $result['errors'],
                'old'        => $result['data'],
                'action'     => '/comercial/entidades',
            ]);
            return;
        }

        try {
            $repo = $this->entidades;
            $id   = $repo->create($result['data']);
            $repo->replaceServicios($id, $result['data']['servicios']);
        } catch (PDOException $e) {
            Logger::error($e, 'EntidadesController::create');
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            return;
        }

        redirect('/comercial/entidades?created=1');
    }

    public function editForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) { redirect('/comercial/entidades'); }

        $repo = $this->entidades;
        $row  = $repo->findById($id);
        if (!$row) { redirect('/comercial/entidades'); }
        $row['servicios'] = $repo->serviciosDeEntidad($id);

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
            'csrf'       => csrf_token(),
            'provincias' => $provincias, // <<<<<
            'action'     => '/comercial/entidades/' . $id,
            'cantones'   => $cantones,
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
        ]);
    }

    public function update($id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)$id;
        if ($id < 1) { redirect('/comercial/entidades'); return; }

        $dto           = $this->makeDto($_POST);
        $dto['id']     = $id;
        $result        = $this->validateDto($dto);
        $result['data']['id'] = $id;

        if ($result['errors'] !== []) {
            http_response_code(400);
            $repo   = $this->entidades;
            $stored = $repo->findById($id) ?? [];
            $stored['servicios'] = $repo->serviciosDeEntidad($id);
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Editar']];
            view('comercial/entidades/edit', [
                'title'      => 'Editar Cooperativa',
                'crumbs'     => $crumbs,
                'item'       => $stored,
                'csrf'       => csrf_token(),
                'provincias' => $this->ubicaciones->provincias(),
                'cantones'   => $this->ubicaciones->cantones((int)($result['data']['provincia_id'] ?? $stored['provincia_id'] ?? 0)),
                'segmentos'  => $repo->segmentos(),
                'servicios'  => $repo->servicios(),
                'errors'     => $result['errors'],
                'old'        => $result['data'],
                'action'     => '/comercial/entidades/' . $id,
            ]);
            return;
        }

        try {
            $repo = $this->entidades;
            $repo->update($id, $result['data']);
            $repo->replaceServicios($id, $result['data']['servicios']);
        } catch (PDOException $e) {
            Logger::error($e, 'EntidadesController::update');
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            return;
        }

        redirect('/comercial/entidades/' . $id . '/edit?ok=1');
    }

    public function delete(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) { http_response_code(400); echo 'CSRF inválido'; return; }
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
