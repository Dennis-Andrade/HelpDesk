<?php
namespace App\Controllers\Comercial;


use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\ValidationService;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\UbicacionesService;
use function \view;
use function \redirect;
use function \csrf_token;
use function \csrf_verify;
use function Config\db;

final class EntidadesController
{
    public function index(): void
    public function index()
    {
        $crumbs = Breadcrumbs::make([
            ['href'=>'/comercial', 'label'=>'Comercial'],
            ['label'=>'Entidades Financieras']
        ]);
        $q  = trim($_GET['q'] ?? '');
        $pg = Pagination::fromRequest($_GET, 1, 20, 100);
        $rs = (new BuscarEntidadesService())->buscar($q, $pg->page, $pg->perPage);

        view('comercial/entidades/index', [
            'title'   => 'Comercial · Entidades',
            'crumbs'  => $crumbs,
            'items'   => $rs['items'],
            'total'   => $rs['total'],
            'page'    => $rs['page'],
            'perPage' => $rs['perPage'],
        $q        = trim($_GET['q'] ?? '');
        $filters  = is_array($_GET) ? $_GET : [];
        $pager    = Pagination::fromRequest($_GET, 1, 20, 0);
        $service  = new BuscarEntidadesService();
        $result   = $service->buscar($q, $pager->page, $pager->perPage);

        return view('comercial/entidades/index', [
            'layout'  => 'layout',
            'title'   => 'Entidades financieras',
            'items'   => $result['items'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'perPage' => $result['perPage'],
            'q'       => $q,
            'csrf'    => csrf_token()
            'csrf'    => csrf_token(),
            'filters' => $filters,
        ]);
    }
    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) { echo json_encode(['error'=>'id inválido']); return; }
        $this->showJson($id);
    }

        $repo = new \App\Repositories\Comercial\EntidadRepository();
        $row  = $repo->findDetalles($id);
        $sv   = $repo->serviciosActivos($id);
    public function showJson($id): void
    {
        $id = (int) $id;

        if (!$row) { echo json_encode(['error'=>'no encontrado']); return; }
        if ($id < 1) {
            $this->respondEntidadJson(['error' => 'ID inválido'], 400);
            return;
        }

        $data = [
            'nombre'         => $row['nombre'],
            'ruc'            => $row['ruc'] ?? null,
            'telefono_fijo'  => $row['telefono_fijo_1'] ?? null,
            'telefono_movil' => $row['telefono_movil'] ?? null,
            'email'          => $row['email'] ?? null,
            'tipo'           => $row['tipo_entidad'] ?? null,
            'segmento'       => $row['id_segmento'] ? ('Segmento ' . (int)$row['id_segmento']) : 'No especificado',
            'ubicacion'      => trim(($row['provincia'] ?? '') . (($row['provincia']??'') && ($row['canton']??'') ? ' - ' : '') . ($row['canton'] ?? '')) ?: 'No especificado',
            'notas'          => $row['notas'] ?? null,
            'servicios'      => $sv,
        ];
        try {
            $data = $this->loadEntidadDetalle($id);
            if ($data === null) {
                $this->respondEntidadJson(['error' => 'No se pudo obtener la entidad'], 404);
                return;
            }

        echo json_encode($data);
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

        $provincias = (new UbicacionesService())->provincias();

        view('comercial/entidades/create', [
            'title'      => 'Nueva Entidad',
            'crumbs'     => $crumbs,
            'csrf'       => csrf_token(),
            'provincias' => $provincias, // <<<<<
        ]);
    }

    public function create(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) { http_response_code(400); echo 'CSRF inválido'; return; }

        $val  = new ValidationService();
        $repo = new EntidadRepository();
        $res  = $val->validarCooperativa($_POST);
@@ -159,26 +160,66 @@ final class EntidadesController
                'crumbs'=>$crumbs,
                'item'=>$row,
                'csrf'=>csrf_token(),
                'provincias'=>$ubi->provincias(),
                'segmentos'=>$repo->segmentos(),
                'servicios'=>$repo->servicios(),
                'tipos'=>['cooperativa','mutualista','sujeto obligado no financiero','caja de ahorros','casa de valores'],
                'errors'=>$res['errors'],
                'sel'=>array_map('intval',(array)($_POST['servicios'] ?? []))
            ]);
            return;
        }

        $repo->update($id, $res['data']);
        $repo->replaceServicios($id, $res['servicios']);
        redirect('/comercial/entidades');
    }

    public function delete(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) { (new EntidadRepository())->delete($id); }
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
        $repo = new EntidadRepository();
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

        return [
            'nombre'         => $row['nombre'],
            'ruc'            => $row['ruc'] ?? null,
            'telefono_fijo'  => $row['telefono_fijo_1'] ?? null,
            'telefono_movil' => $row['telefono_movil'] ?? null,
            'email'          => $row['email'] ?? null,
            'tipo'           => $row['tipo_entidad'] ?? null,
            'segmento'       => $row['id_segmento'] ? ('Segmento ' . (int)$row['id_segmento']) : 'No especificado',
            'ubicacion'      => $ubicacion,
            'notas'          => $row['notas'] ?? null,
            'servicios'      => $servicios,
        ];
    }
}
