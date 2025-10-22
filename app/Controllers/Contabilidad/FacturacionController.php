<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Repositories\Comercial\EntidadRepository;
use App\Repositories\Contabilidad\FacturacionRepository;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\Pagination;
use App\Services\Shared\UbicacionesService;
use App\Support\Logger;
use function redirect;
use function view;

final class FacturacionController
{
    private FacturacionRepository $facturacion;
    private EntidadRepository $entidades;
    private UbicacionesService $ubicaciones;

    public function __construct(
        ?FacturacionRepository $facturacion = null,
        ?EntidadRepository $entidades = null,
        ?UbicacionesService $ubicaciones = null
    ) {
        $this->facturacion = $facturacion ?? new FacturacionRepository();
        $this->entidades   = $entidades ?? new EntidadRepository();
        $this->ubicaciones = $ubicaciones ?? new UbicacionesService();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->facturacion->paginate($filters, $pager->page, $pager->perPage);

        $toast = null;
        if (isset($_GET['saved'])) {
            $toast = ['variant' => 'success', 'message' => 'Datos de facturación guardados correctamente.'];
        }

        view('contabilidad/facturacion/index', [
            'layout'     => 'layout',
            'title'      => 'Datos de facturación',
            'crumbs'     => Breadcrumbs::make([
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['label' => 'Datos de facturación'],
            ]),
            'items'      => $result['items'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'perPage'    => $result['perPage'],
            'filters'    => $filters,
            'provincias' => $this->ubicaciones->provincias(),
            'toast'      => $toast,
        ]);
    }

    public function editForm(): void
    {
        $cooperativaId = isset($_GET['cooperativa']) ? (int)$_GET['cooperativa'] : 0;
        if ($cooperativaId < 1) {
            redirect('/contabilidad/facturacion');
            return;
        }

        $registro = $this->facturacion->findByCooperativa($cooperativaId);
        if ($registro === null) {
            $registro = [
                'id_cooperativa' => $cooperativaId,
                'cooperativa'    => $this->findCooperativaNombre($cooperativaId),
            ];
        }

        $provId  = $registro['provincia_id'] ?? null;
        $cantones = $provId ? $this->ubicaciones->cantones((int)$provId) : [];

        view('contabilidad/facturacion/edit', [
            'layout'      => 'layout',
            'title'       => 'Editar datos de facturación',
            'crumbs'      => Breadcrumbs::make([
                ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                ['href' => '/contabilidad/facturacion', 'label' => 'Datos de facturación'],
                ['label' => 'Editar'],
            ]),
            'item'        => $registro,
            'entidades'   => $this->entidades->listadoLigero(),
            'provincias'  => $this->ubicaciones->provincias(),
            'cantones'    => $cantones,
            'action'      => '/contabilidad/facturacion/guardar',
        ]);
    }

    public function save(): void
    {
        $cooperativaId = isset($_POST['id_cooperativa']) ? (int)$_POST['id_cooperativa'] : 0;
        if ($cooperativaId < 1) {
            redirect('/contabilidad/facturacion');
            return;
        }

        $parsed = $this->parseInput($_POST);
        if ($parsed['errors']) {
            http_response_code(422);
            $registro = $this->facturacion->findByCooperativa($cooperativaId) ?? ['id_cooperativa' => $cooperativaId];
            view('contabilidad/facturacion/edit', [
                'layout'      => 'layout',
                'title'       => 'Editar datos de facturación',
                'crumbs'      => Breadcrumbs::make([
                    ['href' => '/contabilidad', 'label' => 'Contabilidad'],
                    ['href' => '/contabilidad/facturacion', 'label' => 'Datos de facturación'],
                    ['label' => 'Editar'],
                ]),
                'item'        => array_merge($registro, $parsed['data']),
                'entidades'   => $this->entidades->listadoLigero(),
                'provincias'  => $this->ubicaciones->provincias(),
                'cantones'    => $parsed['data']['provincia_id'] ? $this->ubicaciones->cantones((int)$parsed['data']['provincia_id']) : [],
                'errors'      => $parsed['errors'],
                'action'      => '/contabilidad/facturacion/guardar',
            ]);
            return;
        }

        try {
            $this->facturacion->save($cooperativaId, $parsed['data']);
        } catch (\Throwable $e) {
            Logger::error($e, 'FacturacionController::save');
            http_response_code(500);
            echo 'No se pudieron guardar los datos de facturación.';
            return;
        }

        redirect('/contabilidad/facturacion?saved=1');
    }

    /**
     * @param array<string,mixed> $source
     * @return array{data:array<string,mixed>,errors:array<string,string>}
     */
    private function parseInput(array $source): array
    {
        $data = [
            'direccion'             => trim((string)($source['direccion'] ?? '')),
            'provincia'             => trim((string)($source['provincia'] ?? '')),
            'canton'                => trim((string)($source['canton'] ?? '')),
            'provincia_id'          => $source['provincia_id'] !== '' ? (int)$source['provincia_id'] : null,
            'canton_id'             => $source['canton_id'] !== '' ? (int)$source['canton_id'] : null,
            'email1'                => trim((string)($source['email1'] ?? '')),
            'email2'                => trim((string)($source['email2'] ?? '')),
            'email3'                => trim((string)($source['email3'] ?? '')),
            'email4'                => trim((string)($source['email4'] ?? '')),
            'email5'                => trim((string)($source['email5'] ?? '')),
            'tel_fijo1'             => trim((string)($source['tel_fijo1'] ?? '')),
            'tel_fijo2'             => trim((string)($source['tel_fijo2'] ?? '')),
            'tel_fijo3'             => trim((string)($source['tel_fijo3'] ?? '')),
            'tel_cel1'              => trim((string)($source['tel_cel1'] ?? '')),
            'tel_cel2'              => trim((string)($source['tel_cel2'] ?? '')),
            'tel_cel3'              => trim((string)($source['tel_cel3'] ?? '')),
            'contabilidad_nombre'   => trim((string)($source['contabilidad_nombre'] ?? '')),
            'contabilidad_telefono' => trim((string)($source['contabilidad_telefono'] ?? '')),
        ];

        $errors = [];

        if ($data['direccion'] === '') {
            $errors['direccion'] = 'Ingresa la dirección de facturación.';
        }

        $emailPrincipal = $data['email1'];
        if ($emailPrincipal === '' && $data['email2'] === '' && $data['email3'] === '' && $data['email4'] === '' && $data['email5'] === '') {
            $errors['email1'] = 'Ingresa al menos un correo electrónico corporativo.';
        }

        if ($emailPrincipal !== '' && !filter_var($emailPrincipal, FILTER_VALIDATE_EMAIL)) {
            $errors['email1'] = 'Correo electrónico inválido.';
        }

        if ($data['contabilidad_telefono'] !== '' && !preg_match('/^\+?[0-9]{6,15}$/', $data['contabilidad_telefono'])) {
            $errors['contabilidad_telefono'] = 'Número de teléfono inválido.';
        }

        return ['data' => $data, 'errors' => $errors];
    }

    private function findCooperativaNombre(int $id): string
    {
        foreach ($this->entidades->listadoLigero() as $entidad) {
            if ((int)($entidad['id'] ?? 0) === $id) {
                return (string)($entidad['nombre'] ?? '');
            }
        }
        return '';
    }
}
