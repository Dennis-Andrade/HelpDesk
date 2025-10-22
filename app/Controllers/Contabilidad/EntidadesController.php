<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\ValidationService;
use App\Services\Shared\UbicacionesService;
use App\Services\Shared\EntidadLogoStorage;
use App\Support\Logger;
use function redirect;
use function view;

final class EntidadesController
{
    private const TIPOS_ENTIDAD = [
        'cooperativa',
        'mutualista',
        'sujeto obligado no financiero',
        'caja de ahorros',
        'casa de valores',
    ];

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

    public function index(): void
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

        view('comercial/entidades/index', [
            'layout'       => 'layout',
            'title'        => 'Contabilidad · Entidades',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'q'            => $q,
            'filters'      => $filters,
            'toastMessage' => $toastMessage,
            'modulePrefix' => 'contabilidad',
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
            Logger::error($e, 'Contabilidad\\EntidadesController::suggest');
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

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/entidades', 'label' => 'Entidades'],
            ['label' => 'Crear'],
        ]);

        $repo = $this->entidades;
        view('contabilidad/entidades/create', [
            'title'      => 'Nueva Entidad',
            'crumbs'     => $crumbs,
            'provincias' => $this->ubicaciones->provincias(),
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
            'tipos'      => self::TIPOS_ENTIDAD,
            'action'     => '/contabilidad/entidades',
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
            $crumbs = [
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['href' => '/contabilidad/entidades', 'label' => 'Entidades'],
                ['label' => 'Crear'],
            ];
            view('contabilidad/entidades/create', [
                'title'      => 'Nueva Entidad',
                'crumbs'     => $crumbs,
                'provincias' => $this->ubicaciones->provincias(),
                'cantones'   => $this->ubicaciones->cantones((int)($res['data']['provincia_id'] ?? 0)),
                'segmentos'  => $repo->segmentos(),
                'servicios'  => $repo->servicios(),
                'tipos'      => self::TIPOS_ENTIDAD,
                'errors'     => $res['errors'],
                'old'        => $res['data'],
                'action'     => '/contabilidad/entidades',
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
                    Logger::error($cleanup, 'Contabilidad\\EntidadesController::create cleanup');
                }
            }
            Logger::error($e, 'Contabilidad\\EntidadesController::create');
            http_response_code(500);
            echo 'No se pudo guardar la entidad';
            return;
        }

        redirect('/contabilidad/entidades?created=1');
    }

    public function editForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            redirect('/contabilidad/entidades');
        }

        $repo = $this->entidades;
        $row  = $repo->findById($id);
        if (!$row) {
            redirect('/contabilidad/entidades');
        }

        $row['id']             = (int)($row['id'] ?? $row['id_cooperativa'] ?? $id);
        $row['id_entidad']     = (int)($row['id_entidad'] ?? $row['id'] ?? $id);
        $row['id_cooperativa'] = (int)($row['id_cooperativa'] ?? $row['id'] ?? $id);
        $row['servicios']      = $repo->serviciosDeEntidad($id);

        try {
            $detalle = $repo->findDetalles($id);
        } catch (\Throwable $e) {
            $detalle = null;
            Logger::error($e, 'Contabilidad\\EntidadesController::editForm detalle');
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
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/entidades', 'label' => 'Entidades'],
            ['label' => 'Editar'],
        ]);

        view('contabilidad/entidades/edit', [
            'title'      => 'Editar Entidad',
            'crumbs'     => $crumbs,
            'item'       => $row,
            'provincias' => $this->ubicaciones->provincias(),
            'cantones'   => $this->ubicaciones->cantones((int)($row['provincia_id'] ?? 0)),
            'segmentos'  => $repo->segmentos(),
            'servicios'  => $repo->servicios(),
            'tipos'      => self::TIPOS_ENTIDAD,
            'action'     => '/contabilidad/entidades/' . $id,
        ]);
    }

    public function update($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            $id = (int)($_POST['id'] ?? 0);
        }
        if ($id < 1) {
            redirect('/contabilidad/entidades');
            return;
        }

        $repo = $this->entidades;
        $existing = $repo->findById($id);
        if (!$existing) {
            redirect('/contabilidad/entidades');
            return;
        }

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
            $row                    = array_merge($existing, $res['data']);
            $row['id']              = $id;
            $row['id_entidad']      = $id;
            $row['id_cooperativa']  = $id;
            $row['servicios']       = $repo->serviciosDeEntidad($id);
            $crumbs = [
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['href' => '/contabilidad/entidades', 'label' => 'Entidades'],
                ['label' => 'Editar'],
            ];
            view('contabilidad/entidades/edit', [
                'title'      => 'Editar Entidad',
                'crumbs'     => $crumbs,
                'item'       => $row,
                'provincias' => $this->ubicaciones->provincias(),
                'cantones'   => $this->ubicaciones->cantones((int)($row['provincia_id'] ?? $res['data']['provincia_id'] ?? 0)),
                'segmentos'  => $repo->segmentos(),
                'servicios'  => $repo->servicios(),
                'tipos'      => self::TIPOS_ENTIDAD,
                'errors'     => $res['errors'],
                'old'        => $res['data'],
                'action'     => '/contabilidad/entidades/' . $id,
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
            Logger::error($e, 'Contabilidad\\EntidadesController::update');
            http_response_code(500);
            echo 'No se pudo actualizar la entidad';
            return;
        }

        redirect('/contabilidad/entidades?ok=1');
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
                Logger::error($e, 'Contabilidad\\EntidadesController::delete');
            }
        }
        redirect('/contabilidad/entidades?deleted=1');
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
}
