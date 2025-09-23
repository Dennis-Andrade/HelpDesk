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
            'q'       => $q,
            'csrf'    => csrf_token()
        ]);
    }
    public function show(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) { echo json_encode(['error'=>'id inválido']); return; }

        $repo = new \App\Repositories\Comercial\EntidadRepository();
        $row  = $repo->findDetalles($id);
        $sv   = $repo->serviciosActivos($id);

        if (!$row) { echo json_encode(['error'=>'no encontrado']); return; }

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

        echo json_encode($data);
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

        if (!$res['ok']) {
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Crear']];
            $ubi = new UbicacionesService();
            view('comercial/entidades/create', [
                'title'=>'Nueva Cooperativa',
                'crumbs'=>$crumbs,
                'csrf'=>csrf_token(),
                'provincias'=>$ubi->provincias(),
                'segmentos'=>$repo->segmentos(),
                'servicios'=>$repo->servicios(),
                'tipos'=>['cooperativa','mutualista','sujeto obligado no financiero','caja de ahorros','casa de valores'],
                'errors'=>$res['errors'],
                'old'=>$res['data']
            ]);
            return;
        }

        $id = $repo->create($res['data']);
        $repo->replaceServicios($id, $res['servicios']);
        redirect('/comercial/entidades');
    }

    public function editForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) { redirect('/comercial/entidades'); }

        $repo = new EntidadRepository();
        $row  = $repo->findById($id);
        if (!$row) { redirect('/comercial/entidades'); }

        $crumbs = Breadcrumbs::make([
            ['href'=>'/comercial', 'label'=>'Comercial'],
            ['href'=>'/comercial/entidades', 'label'=>'Entidades'],
            ['label'=>'Editar']
        ]);

        $provincias = (new UbicacionesService())->provincias();

        view('comercial/entidades/edit', [
            'title'      => 'Editar Entidad',
            'crumbs'     => $crumbs,
            'item'       => $row,
            'csrf'       => csrf_token(),
            'provincias' => $provincias, // <<<<<
        ]);
    }


    public function update(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) { redirect('/comercial/entidades'); }

        $val  = new ValidationService();
        $repo = new EntidadRepository();
        $res  = $val->validarCooperativa($_POST);

        if (!$res['ok']) {
            $ubi = new UbicacionesService();
            $row = array_merge((array)$repo->findById($id), $res['data']);
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Editar']];
            view('comercial/entidades/edit', [
                'title'=>'Editar Cooperativa',
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
}
