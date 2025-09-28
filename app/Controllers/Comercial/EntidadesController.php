<?php
declare(strict_types=1);

namespace App\Controllers\Comercial;

use App\Repositories\Comercial\EntidadQueryRepository;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\UbicacionesService;
use App\Support\Logger;
use Config\Cnxn;
use function csrf_token;
use function csrf_verify;
use function redirect;
use function view;

final class EntidadesController
{
    private BuscarEntidadesService $buscarEntidades;
    private EntidadRepository $repo;
    private EntidadQueryRepository $queryRepo;
    private UbicacionesService $ubicaciones;

    public function __construct(
        ?BuscarEntidadesService $buscarEntidades = null,
        ?EntidadRepository $repo = null,
        ?EntidadQueryRepository $queryRepo = null,
        ?UbicacionesService $ubicaciones = null
    ) {
        $pdo = Cnxn::pdo();
        $this->repo           = $repo ?? new EntidadRepository($pdo);
        $this->queryRepo      = $queryRepo ?? new EntidadQueryRepository($pdo);
        $this->ubicaciones    = $ubicaciones ?? new UbicacionesService();
        $this->buscarEntidades = $buscarEntidades ?? new BuscarEntidadesService($this->queryRepo);
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

    private function normalize(array $in): array
    {
        $tipo = $in['tipo_entidad'] ?? 'cooperativa';
        $data = [
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
            'id_segmento'     => $tipo === 'cooperativa' ? $this->intOrNull($in['id_segmento'] ?? '') : null,
            'notas'           => $this->strOrNull($in['notas'] ?? ''),
            'servicios'       => $this->intsFromArray($in['servicios'] ?? []),
        ];
        if (in_array(1, $data['servicios'], true)) {
            $data['servicios'] = [1];
        }
        return $data;
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
        $this->showJson(['id' => $id]);
    }

    public function showJson($params): void
    {
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;

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
        } catch (\Throwable $e) {
            $this->respondEntidadJson(['error' => 'Error interno'], 500);
        }
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/comercial', 'label' => 'Comercial'],
            ['href' => '/comercial/entidades', 'label' => 'Entidades'],
            ['label' => 'Crear'],
        ]);

        $provincias = $this->ubicaciones->provincias();

        view('comercial/entidades/create', [
            'title'      => 'Nueva Entidad',
            'crumbs'     => $crumbs,
            'csrf'       => csrf_token(),
            'provincias' => $provincias,
            'segmentos'  => $this->queryRepo->segmentos(),
            'servicios'  => $this->queryRepo->servicios(),
            'action'     => '/comercial/entidades',
            'isCreate'   => true,
        ]);
    }

    public function create()
    {
        if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
            http_response_code(419);
            echo 'CSRF inválido';
            return;
        }
        try {
            $d = $this->normalize($_POST);
            if ($d['nombre'] === '') {
                throw new \InvalidArgumentException('Nombre requerido');
            }
            if ($d['ruc'] !== null && (strlen($d['ruc']) < 10 || strlen($d['ruc']) > 13)) {
                throw new \InvalidArgumentException('RUC inválido');
            }

            $this->repo->create($d);
            header('Location: /comercial/entidades?created=1');
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            error_log('[CREATE] ' . $e->getCode() . ' ' . ($e->errorInfo[2] ?? $e->getMessage()));
        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    public function editForm($params = []): void
    {
        $id = isset($params['id']) ? (int)$params['id'] : (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            redirect('/comercial/entidades');
            return;
        }

        $row = $this->queryRepo->findForEdit($id);
        if (!$row) {
            redirect('/comercial/entidades');
            return;
        }

        $crumbs = Breadcrumbs::make([
            ['href' => '/comercial', 'label' => 'Comercial'],
            ['href' => '/comercial/entidades', 'label' => 'Entidades'],
            ['label' => 'Editar'],
        ]);

        $provincias = $this->ubicaciones->provincias();
        $cantones   = $this->ubicaciones->cantones((int)($row['provincia_id'] ?? 0));

        view('comercial/entidades/edit', [
            'title'      => 'Editar Entidad',
            'crumbs'     => $crumbs,
            'item'       => $row,
            'csrf'       => csrf_token(),
            'provincias' => $provincias,
            'cantones'   => $cantones,
            'segmentos'  => $this->queryRepo->segmentos(),
            'servicios'  => $this->queryRepo->servicios(),
            'action'     => '/comercial/entidades/' . $id,
            'isCreate'   => false,
        ]);
    }

    public function update($params)
    {
        if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
            http_response_code(419);
            echo 'CSRF inválido';
            return;
        }
        $id = is_array($params) ? (int)($params['id'] ?? 0) : (int)$params;
        if ($id < 1) {
            $id = (int)($_POST['id'] ?? 0);
        }
        if ($id < 1) {
            redirect('/comercial/entidades');
            return;
        }

        try {
            $d = $this->normalize($_POST);
            if ($d['nombre'] === '') {
                throw new \InvalidArgumentException('Nombre requerido');
            }
            if ($d['ruc'] !== null && (strlen($d['ruc']) < 10 || strlen($d['ruc']) > 13)) {
                throw new \InvalidArgumentException('RUC inválido');
            }

            $this->repo->update($id, $d);
            header('Location: /comercial/entidades/' . $id . '/edit?ok=1');
            exit;
        } catch (\PDOException $e) {
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            error_log('[UPDATE] ' . $e->getCode() . ' ' . ($e->errorInfo[2] ?? $e->getMessage()));
        } catch (\Throwable $e) {
            http_response_code(400);
            echo $e->getMessage();
        }
    }

    public function delete(): void
    {
        if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
            http_response_code(419);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $this->repo->delete($id);
            } catch (\Throwable $e) {
                Logger::error($e, 'EntidadesController::delete');
            }
        }
        redirect('/comercial/entidades');
    }

    private function respondEntidadJson(array $payload, $status = 200): void
    {
        http_response_code((int)$status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function loadEntidadDetalle(int $id): ?array
    {
        $row = $this->queryRepo->findDetalles($id);
        if (!$row) {
            return null;
        }

        $servicios = $this->queryRepo->serviciosActivos($id);

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
