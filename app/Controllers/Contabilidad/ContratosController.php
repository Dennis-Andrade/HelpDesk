<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Repositories\Comercial\EntidadRepository;
use App\Repositories\Contabilidad\ContratoRepository;
use App\Repositories\Contabilidad\HistorialRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Support\Logger;
use function redirect;
use function view;
use function bin2hex;
use function is_dir;
use function is_uploaded_file;
use function mkdir;
use function move_uploaded_file;
use function pathinfo;
use function random_bytes;
use function realpath;
use function str_replace;

final class ContratosController
{
    private const IVA_RATE = 0.15;

    private ContratoRepository $contratos;
    private EntidadRepository $entidades;
    private HistorialRepository $historial;

    public function __construct(
        ?ContratoRepository $contratos = null,
        ?EntidadRepository $entidades = null,
        ?HistorialRepository $historial = null
    ) {
        $this->contratos  = $contratos ?? new ContratoRepository();
        $this->entidades  = $entidades ?? new EntidadRepository();
        $this->historial  = $historial ?? new HistorialRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->contratos->paginate($filters, $pager->page, $pager->perPage);

        $toast = null;
        if (isset($_GET['created'])) {
            $toast = ['variant' => 'success', 'message' => 'Contrato registrado correctamente.'];
        } elseif (isset($_GET['updated'])) {
            $toast = ['variant' => 'success', 'message' => 'Contrato actualizado correctamente.'];
        } elseif (isset($_GET['deleted'])) {
            $toast = ['variant' => 'success', 'message' => 'Contrato eliminado correctamente.'];
        }

        view('contabilidad/contratos/index', [
            'layout'     => 'layout',
            'title'      => 'Contratos digitales',
            'crumbs'     => Breadcrumbs::make([
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['label' => 'Contratos digitales'],
            ]),
            'items'      => $result['items'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'perPage'    => $result['perPage'],
            'filters'    => $filters,
            'servicios'  => $this->contratos->servicios(),
            'redes'      => $this->contratos->redes(),
            'historialEstados' => $this->historial->estados(),
            'toast'      => $toast,
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/contratos', 'label' => 'Contratos digitales'],
            ['label' => 'Nuevo contrato'],
        ]);

        view('contabilidad/contratos/create', [
            'layout'     => 'layout',
            'title'      => 'Nuevo contrato',
            'crumbs'     => $crumbs,
            'entidades'  => $this->entidades->listadoLigero(),
            'servicios'  => $this->contratos->servicios(),
            'redes'      => $this->contratos->redes(),
            'estados'    => $this->contratos->estadosPago(),
            'periodos'   => $this->periodos(),
            'tiposContrato' => $this->tiposContrato(),
            'action'     => '/contabilidad/contratos',
        ]);
    }

    public function create(): void
    {
        $parsed = $this->parseInput($_POST, $_FILES ?? []);
        if ($parsed['errors']) {
            http_response_code(422);
            $crumbs = Breadcrumbs::make([
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['href' => '/contabilidad/contratos', 'label' => 'Contratos digitales'],
                ['label' => 'Nuevo contrato'],
            ]);
            view('contabilidad/contratos/create', [
                'layout'     => 'layout',
                'title'      => 'Nuevo contrato',
                'crumbs'     => $crumbs,
                'entidades'  => $this->entidades->listadoLigero(),
                'servicios'  => $this->contratos->servicios(),
                'redes'      => $this->contratos->redes(),
                'estados'    => $this->contratos->estadosPago(),
                'periodos'   => $this->periodos(),
                'tiposContrato' => $this->tiposContrato(),
                'errors'     => $parsed['errors'],
                'old'        => $parsed['data'],
                'action'     => '/contabilidad/contratos',
            ]);
            return;
        }

        try {
            $this->contratos->create($parsed['data']);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContratosController::create');
            http_response_code(500);
            echo 'No se pudo registrar el contrato.';
            return;
        }

        redirect('/contabilidad/contratos?created=1');
    }

    public function editForm(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            redirect('/contabilidad/contratos');
            return;
        }

        $item = $this->contratos->find($id);
        if ($item === null) {
            redirect('/contabilidad/contratos');
            return;
        }

        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/contratos', 'label' => 'Contratos digitales'],
            ['label' => 'Editar contrato'],
        ]);

        view('contabilidad/contratos/edit', [
            'layout'     => 'layout',
            'title'      => 'Editar contrato',
            'crumbs'     => $crumbs,
            'item'       => $item,
            'entidades'  => $this->entidades->listadoLigero(),
            'servicios'  => $this->contratos->servicios(),
            'redes'      => $this->contratos->redes(),
            'estados'    => $this->contratos->estadosPago(),
            'periodos'   => $this->periodos(),
            'tiposContrato' => $this->tiposContrato(),
            'action'     => '/contabilidad/contratos/' . $id,
        ]);
    }

    public function update($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/contabilidad/contratos');
            return;
        }

        $parsed = $this->parseInput($_POST, $_FILES ?? [], $id);
        if ($parsed['errors']) {
            http_response_code(422);
            $crumbs = Breadcrumbs::make([
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['href' => '/contabilidad/contratos', 'label' => 'Contratos digitales'],
                ['label' => 'Editar contrato'],
            ]);
            view('contabilidad/contratos/edit', [
                'layout'     => 'layout',
                'title'      => 'Editar contrato',
                'crumbs'     => $crumbs,
                'item'       => array_merge($parsed['data'], ['id' => $id]),
                'entidades'  => $this->entidades->listadoLigero(),
                'servicios'  => $this->contratos->servicios(),
                'redes'      => $this->contratos->redes(),
                'estados'    => $this->contratos->estadosPago(),
                'periodos'   => $this->periodos(),
                'tiposContrato' => $this->tiposContrato(),
                'errors'     => $parsed['errors'],
                'action'     => '/contabilidad/contratos/' . $id,
            ]);
            return;
        }

        try {
            $this->contratos->update($id, $parsed['data']);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContratosController::update');
            http_response_code(500);
            echo 'No se pudo actualizar el contrato.';
            return;
        }

        redirect('/contabilidad/contratos?updated=1');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            redirect('/contabilidad/contratos');
            return;
        }

        try {
            $this->contratos->delete($id);
        } catch (\Throwable $e) {
            Logger::error($e, 'ContratosController::delete');
            redirect('/contabilidad/contratos?error=1');
            return;
        }

        redirect('/contabilidad/contratos?deleted=1');
    }

    /**
     * @return array<int,string>
     */
    private function periodos(): array
    {
        return ['Mensual', 'Trimestral', 'Semestral', 'Anual', 'Indefinido'];
    }

    /**
     * @return array<int,string>
     */
    private function tiposContrato(): array
    {
        return ['Mensual', 'Trimestral', 'Semestral', 'Anual', 'Indefinido'];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{data:array<string,mixed>,errors:array<string,string>}
     */
    private function parseInput(array $post, array $files, ?int $contractId = null): array
    {
        $activoSeleccion = $post['activo'] ?? null;
        $activo = true;
        if ($activoSeleccion !== null) {
            $valor = strtolower((string)$activoSeleccion);
            $activo = !in_array($valor, ['0', 'false', 'off'], true);
        }

        $serviciosSeleccionados = $post['servicios'] ?? $post['servicios_ids'] ?? [];
        if (!is_array($serviciosSeleccionados)) {
            $serviciosSeleccionados = [$serviciosSeleccionados];
        }
        $serviciosSeleccionados = array_values(array_unique(array_filter(
            array_map('intval', $serviciosSeleccionados),
            static fn($id) => $id > 0
        )));

        $servicioPrincipal = isset($post['id_servicio']) ? (int)$post['id_servicio'] : 0;
        if ($servicioPrincipal <= 0 && !empty($serviciosSeleccionados)) {
            $servicioPrincipal = $serviciosSeleccionados[0];
        }

        $data = [
            'id_cooperativa'     => isset($post['id_cooperativa']) ? (int)$post['id_cooperativa'] : 0,
            'id_servicio'        => $servicioPrincipal,
            'servicios_ids'      => $serviciosSeleccionados,
            'periodo_facturacion'=> trim((string)($post['periodo'] ?? '')),
            'tipo_contrato'      => trim((string)($post['tipo_contrato'] ?? '')),
            'terminacion_contrato'=> trim((string)($post['terminacion_contrato'] ?? '')),
            'estado_pago'        => trim((string)($post['estado'] ?? 'pendiente')),
            'activo'             => $activo,
            'fecha_contratacion' => trim((string)($post['fecha_contratacion'] ?? '')),
            'fecha_caducidad'    => trim((string)($post['fecha_caducidad'] ?? '')),
            'fecha_desvinculacion'=> trim((string)($post['fecha_desvinculacion'] ?? '')),
            'fecha_finalizacion' => trim((string)($post['fecha_finalizacion'] ?? '')),
            'numero_licencias'   => isset($post['numero_licencias']) ? (int)$post['numero_licencias'] : null,
            'fecha_ultimo_pago'  => trim((string)($post['fecha_ultimo_pago'] ?? '')),
            'codigo_red'         => trim((string)($post['codigo_red'] ?? '')),
            'observaciones'      => trim((string)($post['observaciones'] ?? '')),
        ];

        $ivaPorcentaje = $this->toFloat($post['iva_porcentaje'] ?? (self::IVA_RATE * 100));
        if ($ivaPorcentaje <= 0) {
            $ivaPorcentaje = self::IVA_RATE * 100;
        }
        $valorBase = $this->toFloat($post['valor_base'] ?? $post['valor_contratado'] ?? 0);
        $valorIva  = $this->toFloat($post['valor_iva'] ?? ($valorBase * ($ivaPorcentaje / 100)));
        $valorTotal = $this->toFloat($post['valor_total'] ?? ($valorBase + $valorIva));

        $data['valor_base']       = $valorBase;
        $data['valor_contratado'] = $valorBase;
        $data['valor_iva']        = $valorIva;
        $data['valor_total']      = $valorTotal;
        $data['valor_individual'] = $this->nullableFloat($post['valor_individual'] ?? null);
        $data['valor_grupal']     = $this->nullableFloat($post['valor_grupal'] ?? null);
        $data['iva_porcentaje']   = $ivaPorcentaje;

        $errors = [];
        if ($data['id_cooperativa'] <= 0) {
            $errors['id_cooperativa'] = 'Selecciona la entidad.';
        }
        if ($data['id_servicio'] <= 0) {
            $errors['id_servicio'] = 'Selecciona al menos un servicio.';
        }
        if ($data['fecha_contratacion'] === '') {
            $errors['fecha_contratacion'] = 'Define la fecha de suscripción.';
        }
        if ($valorBase <= 0) {
            $errors['valor_base'] = 'Ingresa un monto base válido.';
        }
        if ($data['periodo_facturacion'] === '') {
            $errors['periodo_facturacion'] = 'Selecciona el periodo de facturación.';
        }
        if ($data['terminacion_contrato'] !== '') {
            $data['terminacion_contrato'] = substr($data['terminacion_contrato'], 0, 255);
        }
        $tiposContrato = $this->tiposContrato();
        if ($data['tipo_contrato'] === '' || !in_array($data['tipo_contrato'], $tiposContrato, true)) {
            $errors['tipo_contrato'] = 'Selecciona el tipo de contrato.';
        }

        if ($data['fecha_finalizacion'] !== '') {
            $normalizada = $this->parseDate($data['fecha_finalizacion']);
            if ($normalizada === null) {
                $errors['fecha_finalizacion'] = 'Define una fecha de terminación válida (AAAA-MM-DD).';
            } else {
                $data['fecha_finalizacion'] = $normalizada;
            }
        } else {
            $data['fecha_finalizacion'] = null;
        }

        $fechaValidaciones = [
            'fecha_contratacion'  => 'Define una fecha de suscripción válida.',
            'fecha_caducidad'     => 'Ingresa una fecha de caducidad válida.',
            'fecha_desvinculacion'=> 'Ingresa una fecha de desvinculación válida.',
            'fecha_ultimo_pago'   => 'Define una fecha de último pago válida.',
        ];

        foreach ($fechaValidaciones as $campo => $mensaje) {
            if ($data[$campo] === '') {
                $data[$campo] = null;
                continue;
            }
            $normalizada = $this->parseDate((string)$data[$campo]);
            if ($normalizada === null) {
                $errors[$campo] = $mensaje;
            } else {
                $data[$campo] = $normalizada;
            }
        }

        $documentoPath = null;
        if (isset($files['documento']) && is_array($files['documento']) && ($files['documento']['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_NO_FILE) {
            $upload = $this->handleUpload($files['documento'], $contractId);
            if ($upload['error'] !== null) {
                $errors['documento'] = $upload['error'];
            } else {
                $documentoPath = $upload['path'];
            }
        } elseif ($contractId !== null) {
            $existing = $this->contratos->find($contractId);
            if ($existing !== null) {
                $documentoPath = $existing['documento_contable'] ?? null;
            }
        }

        $data['documento_contable'] = $documentoPath;

        return ['data' => $data, 'errors' => $errors];
    }

    private function parseDate(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        $dt = date_create($trimmed);
        return $dt instanceof \DateTimeInterface ? $dt->format('Y-m-d') : null;
    }

    /**
     * @param array<string,mixed> $file
     * @return array{path:?string,error:?string}
     */
    private function handleUpload(array $file, ?int $contractId = null): array
    {
        if (($file['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'No se pudo cargar el comprobante.'];
        }

        $tmpName = $file['tmp_name'] ?? null;
        if ($tmpName === null || !is_uploaded_file($tmpName)) {
            return ['path' => null, 'error' => 'Archivo inválido.'];
        }

        $extension = pathinfo($file['name'] ?? 'comprobante', PATHINFO_EXTENSION);
        $safeExt   = $extension !== '' ? '.' . strtolower($extension) : '';
        $targetDir = $this->storagePath();
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['path' => null, 'error' => 'No se pudo crear el directorio para documentos.'];
        }

        $fileName = 'contrato_' . ($contractId ?? time()) . '_' . bin2hex(random_bytes(6)) . $safeExt;
        $destination = $targetDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['path' => null, 'error' => 'No se pudo guardar el comprobante.'];
        }

        return ['path' => $this->relativeStoragePath($destination), 'error' => null];
    }

    private function storagePath(): string
    {
        return rtrim(__DIR__ . '/../../../storage/contabilidad/contratos', '/');
    }

    private function relativeStoragePath(string $absolutePath): string
    {
        $storageRoot = realpath(__DIR__ . '/../../../storage');
        $realPath = realpath($absolutePath) ?: $absolutePath;
        if ($storageRoot && strpos($realPath, $storageRoot) === 0) {
            return ltrim(str_replace('\\', '/', substr($realPath, strlen($storageRoot))), '/');
        }
        return $realPath;
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        return (float)str_replace(',', '.', (string)$value);
    }

    private function nullableFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        return $this->toFloat($value);
    }
}
