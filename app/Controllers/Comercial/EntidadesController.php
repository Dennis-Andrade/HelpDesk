<?php
declare(strict_types=1);
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\ValidationService;
use App\Services\Shared\UbicacionesService;
use App\Services\Shared\EntidadLogoStorage;
use App\Support\Logger;
use function \view;
use function \redirect;

final class EntidadesController
{
    private BuscarEntidadesService $buscarEntidades;
    private EntidadRepository $entidades;
    private ValidationService $validator;
    private UbicacionesService $ubicaciones;
    private EntidadLogoStorage $logoStorage;

    public function __construct(
        ?BuscarEntidadesService $buscarEntidades = null,
        ?EntidadRepository $entidades = null,
        ?ValidationService $validator = null,
        ?UbicacionesService $ubicaciones = null,
        ?EntidadLogoStorage $logoStorage = null
    ) {
        $this->entidades       = $entidades ?? new EntidadRepository();
        $this->validator       = $validator ?? new ValidationService();
        $this->ubicaciones     = $ubicaciones ?? new UbicacionesService();
        $this->buscarEntidades = $buscarEntidades ?? new BuscarEntidadesService($this->entidades, $this->validator);
        $this->logoStorage     = $logoStorage ?? new EntidadLogoStorage();
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
            $toastMessage = 'Entidad actualizada correctamente';
        } elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
            $toastMessage = 'Entidad eliminada correctamente';
        }

        return view('comercial/entidades/index', [
            'layout'  => 'layout',
            'title'   => 'Entidades',
            'items'   => $result['items'],
            'total'   => $result['total'],
            'page'    => $result['page'],
            'perPage' => $result['perPage'],
            'q'       => $q,
            'filters' => $filters,
            'toastMessage' => $toastMessage,
            'modulePrefix' => 'comercial',
        ]);
    }

    public function suggest(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $term = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if (mb_strlen($term) < 3) {
            echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $result = $this->buscarEntidades->buscar($term, 1, 7);
        } catch (\Throwable $e) {
            Logger::error($e, 'EntidadesController::suggest');
            http_response_code(500);
            echo json_encode(['items' => [], 'error' => 'No se pudo obtener sugerencias'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $items = [];
        foreach ($result['items'] as $row) {
            $id = isset($row['id']) ? (int)$row['id'] : (int)($row['id_entidad'] ?? 0);
            $nombre = isset($row['nombre']) ? trim((string)$row['nombre']) : '';
            if ($id < 1 || $nombre === '') {
                continue;
            }

            $labelParts = [$nombre];
            $ruc = isset($row['ruc']) && $row['ruc'] !== '' ? (string)$row['ruc'] : null;
            if ($ruc !== null) {
                $labelParts[] = 'RUC ' . $ruc;
            }

            $provincia = isset($row['provincia_nombre']) ? trim((string)$row['provincia_nombre']) : '';
            $canton    = isset($row['canton_nombre']) ? trim((string)$row['canton_nombre']) : '';
            $ubicacionLabel = trim($provincia . ($provincia !== '' && $canton !== '' ? ' - ' : '') . $canton);
            if ($ubicacionLabel !== '') {
                $labelParts[] = $ubicacionLabel;
            }

            $items[] = [
                'id'    => $id,
                'term'  => $nombre,
                'label' => implode(' · ', $labelParts),
                'ruc'   => $ruc,
            ];
        }

        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    }

    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $this->showJson($id);
    }

    public function showJson($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            $this->respondEntidadJson(['error' => 'ID inválido'], 400);
            return;
        }

        try {
            $data = $this->loadEntidadDetalle($id);
            if ($data === null) {
                $this->respondEntidadJson(['error' => 'Entidad no encontrada'], 404);
                return;
            }

            $this->respondEntidadJson($data, 200);
        } catch (\Throwable $e) {
            Logger::error($e, 'EntidadesController::showJson');
            $this->respondEntidadJson(['error' => 'Error interno'], 500);
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
            'provincias' => $provincias,
            'action'     => '/comercial/entidades',
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
        ]);
    }

    public function create(): void
    {
        $repo = $this->entidades;
        $res  = $this->validator->validarEntidad($_POST);

        $logoFile = isset($_FILES['logo']) && is_array($_FILES['logo']) ? $_FILES['logo'] : null;
        $newLogoPath = null;

        if ($this->hasUploadedFile($logoFile)) {
            $logoUpload = $this->logoStorage->store($logoFile);
            if ($logoUpload['error'] !== null) {
                $res['errors']['logo'] = $logoUpload['error'];
                $res['ok'] = false;
            } else {
                $newLogoPath = $logoUpload['path'];
                $res['data']['logo_path'] = $newLogoPath;
            }
        } else {
            $res['data']['logo_path'] = null;
        }

        if (!$res['ok']) {
            if ($newLogoPath !== null) {
                $this->logoStorage->delete($newLogoPath);
                $res['data']['logo_path'] = null;
            }

            http_response_code(400);
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Crear']];
            view('comercial/entidades/create', [
                'title'=>'Nueva Entidad',
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

        unset($res['data']['remove_logo']);

        try {
            $newId = $repo->create($res['data']);
            $repo->replaceServicios($newId, $res['data']['servicios'] ?? []);
        } catch (\Throwable $e) {
            if ($newLogoPath !== null) {
                $this->logoStorage->delete($newLogoPath);
            }
            if (isset($newId)) {
                try {
                    $repo->delete((int)$newId);
                } catch (\Throwable $cleanup) {
                    Logger::error($cleanup, 'EntidadesController::create cleanup');
                }
            }
            Logger::error($e, 'EntidadesController::create');
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            return;
        }
        $query = http_build_query(['created' => 1]);
        redirect('/comercial/entidades' . ($query !== '' ? '?' . $query : ''));
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
        $row['servicios'] = $repo->serviciosDeEntidad($id);

        try {
            $detalle = $repo->findDetalles($id);
        } catch (\Throwable $e) {
            $detalle = null;
            Logger::error($e, 'EntidadesController::editForm detalle');
        }

        if (is_array($detalle)) {
            if (!isset($row['facturacion_total']) && isset($detalle['total_facturacion'])) {
                $row['facturacion_total'] = (float)$detalle['total_facturacion'];
            }
            if (!isset($row['sic_licencias']) && isset($detalle['sic_licencias'])) {
                $row['sic_licencias'] = (int)$detalle['sic_licencias'];
            }
            if (!isset($row['fecha_registro']) && isset($detalle['fecha_registro'])) {
                $row['fecha_registro'] = $detalle['fecha_registro'];
            }
        }

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
            'provincias' => $provincias,
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
        $existing = $repo->findById($id);
        if (!$existing) { redirect('/comercial/entidades'); return; }
        $res  = $this->validator->validarEntidad($_POST);

        $existingLogo = $this->normalizeLogoPath($existing['logo_path'] ?? null);
        $logoFile = isset($_FILES['logo']) && is_array($_FILES['logo']) ? $_FILES['logo'] : null;
        $newLogoPath = null;
        $logoToDelete = null;

        if ($this->hasUploadedFile($logoFile)) {
            $logoUpload = $this->logoStorage->store($logoFile);
            if ($logoUpload['error'] !== null) {
                $res['errors']['logo'] = $logoUpload['error'];
                $res['ok'] = false;
                $res['data']['logo_path'] = $existingLogo;
            } else {
                $newLogoPath = $logoUpload['path'];
                $res['data']['logo_path'] = $newLogoPath;
                $logoToDelete = $existingLogo;
                unset($res['data']['remove_logo']);
            }
        } elseif ($this->shouldRemoveLogo($_POST)) {
            if ($newLogoPath !== null) {
                $this->logoStorage->delete($newLogoPath);
                $newLogoPath = null;
            }
            $res['data']['logo_path'] = null;
            $res['data']['remove_logo'] = '1';
            if ($existingLogo !== null) {
                $logoToDelete = $existingLogo;
            }
        } else {
            $res['data']['logo_path'] = $existingLogo;
            unset($res['data']['remove_logo']);
        }

        if (!$res['ok']) {
            if ($newLogoPath !== null) {
                $this->logoStorage->delete($newLogoPath);
                $res['data']['logo_path'] = $existingLogo;
            }
            http_response_code(400);
            $row = array_merge($existing, $res['data']);
            $row['id'] = $id;
            $row['id_entidad'] = $id;
            $row['id_cooperativa'] = $id;
            $row['servicios'] = $repo->serviciosDeEntidad($id);
            $crumbs = [['href'=>'/comercial','label'=>'Comercial'],['href'=>'/comercial/entidades','label'=>'Entidades'],['label'=>'Editar']];
            view('comercial/entidades/edit', [
                'title'=>'Editar Entidad',
                'crumbs'=>$crumbs,
                'item'=>$row,
                'provincias'=>$this->ubicaciones->provincias(),
                'cantones'=>$this->ubicaciones->cantones((int)($row['provincia_id'] ?? $res['data']['provincia_id'] ?? 0)),
                'segmentos'=>$repo->segmentos(),
                'servicios'=>$repo->servicios(),
                'tipos'=>['cooperativa','mutualista','sujeto obligado no financiero','caja de ahorros','casa de valores'],
                'errors'=>$res['errors'],
                'old'=>$res['data'],
                'action'=>'/comercial/entidades/' . $id,
                'toastMessage'=>null,
            ]);
            return;
        }

        unset($res['data']['remove_logo']);

        try {
            $repo->update($id, $res['data']);
            $repo->replaceServicios($id, $res['data']['servicios'] ?? []);
            if ($logoToDelete !== null) {
                $this->logoStorage->delete($logoToDelete);
            }
        } catch (\Throwable $e) {
            if ($newLogoPath !== null) {
                $this->logoStorage->delete($newLogoPath);
            }
            Logger::error($e, 'EntidadesController::update');
            http_response_code(500);
            echo 'No se pudo actualizar la entidad';
            return;
        }

        $query = http_build_query(['ok' => 1]);
        redirect('/comercial/entidades' . ($query !== '' ? '?' . $query : ''));
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $logoPath = null;
            try {
                $current = $this->entidades->findById($id);
                if ($current) {
                    $logoPath = $this->normalizeLogoPath($current['logo_path'] ?? null);
                }
                $this->entidades->delete($id);
                if ($logoPath !== null) {
                    $this->logoStorage->delete($logoPath);
                }
            } catch (\Throwable $e) {
                Logger::error($e, 'EntidadesController::delete');
            }
        }
        redirect('/comercial/entidades?deleted=1');
    }

    private function respondEntidadJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param array<string,mixed>|null $file
     */
    private function hasUploadedFile(?array $file): bool
    {
        if ($file === null) {
            return false;
        }
        $error = isset($file['error']) ? (int)$file['error'] : \UPLOAD_ERR_NO_FILE;
        return $error !== \UPLOAD_ERR_NO_FILE;
    }

    private function shouldRemoveLogo(array $post): bool
    {
        return isset($post['remove_logo']) && (string)$post['remove_logo'] === '1';
    }

    /**
     * @param mixed $value
     */
    private function normalizeLogoPath($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
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
        if ($provincia === '' && isset($row['provincia_nombre'])) {
            $provincia = trim((string)$row['provincia_nombre']);
        }
        if ($canton === '' && isset($row['canton_nombre'])) {
            $canton = trim((string)$row['canton_nombre']);
        }

        $ubicacion = trim($provincia . (($provincia !== '' && $canton !== '') ? ' - ' : '') . $canton);
        if ($ubicacion === '') {
            $ubicacion = 'No especificado';
        }

        $calle = trim((string)($row['direccion_calle'] ?? ''));
        $interseccion = trim((string)($row['direccion_interseccion'] ?? ''));
        if ($calle !== '' && $interseccion !== '') {
            $direccionEspecifica = $calle . ' y ' . $interseccion;
        } elseif ($calle !== '') {
            $direccionEspecifica = $calle;
        } elseif ($interseccion !== '') {
            $direccionEspecifica = $interseccion;
        } else {
            $direccionEspecifica = trim((string)($row['direccion_facturacion'] ?? ''));
        }
        if ($direccionEspecifica === '') {
            $direccionEspecifica = 'No especificado';
        }

        $segmentoNombre = trim((string)($row['segmento_nombre'] ?? ''));
        if ($segmentoNombre === '' && !empty($row['id_segmento'])) {
            $segmentoNombre = 'Segmento ' . (int)$row['id_segmento'];
        }
        if ($segmentoNombre === '') {
            $segmentoNombre = 'No especificado';
        }

        $facturacionTotal = isset($row['total_facturacion']) ? (float)$row['total_facturacion'] : 0.0;
        $facturacionFormatted = $facturacionTotal <= 0 ? '$0.00' : ('$' . number_format($facturacionTotal, 2, '.', ','));

        $sicLicencias = isset($row['sic_licencias']) ? (int)$row['sic_licencias'] : 0;
        if ($sicLicencias <= 0) {
            $sicFormateado = 'Sin licencias registradas';
        } else {
            $sicFormateado = $sicLicencias . ' ' . ($sicLicencias === 1 ? 'licencia' : 'licencias');
        }

        $fechaRegistro = isset($row['fecha_registro']) ? trim((string)$row['fecha_registro']) : '';
        $fechaFormateada = 'Sin registro';
        if ($fechaRegistro !== '') {
            $timestamp = strtotime($fechaRegistro);
            if ($timestamp !== false) {
                $fechaFormateada = date('d/m/Y', $timestamp);
            } else {
                $fechaFormateada = $fechaRegistro;
            }
        }

        return [
            'nombre'         => (string)($row['nombre'] ?? ''),
            'ruc'            => $row['ruc'] ?? null,
            'telefono_fijo'  => $row['telefono_fijo'] ?? $row['telefono_fijo_1'] ?? null,
            'telefono_movil' => $row['telefono_movil'] ?? null,
            'email'          => $row['email'] ?? null,
            'tipo'           => $row['tipo_entidad'] ?? null,
            'segmento'       => $segmentoNombre,
            'ubicacion'      => $ubicacion,
            'direccion'      => $direccionEspecifica,
            'notas'          => $row['notas'] ?? null,
            'servicios'      => $servicios,
            'logo_path'      => $this->normalizeLogoPath($row['logo_path'] ?? null),
            'facturacion_total'           => $facturacionTotal,
            'facturacion_total_formatted' => $facturacionFormatted,
            'sic_licencias'               => $sicLicencias,
            'sic_licencias_formatted'     => $sicFormateado,
            'fecha_registro'              => $fechaRegistro !== '' ? $fechaRegistro : null,
            'fecha_registro_formatted'    => $fechaFormateada,
        ];
    }
}
