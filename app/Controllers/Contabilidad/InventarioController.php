<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Repositories\Contabilidad\InventarioRepository;
use App\Services\Shared\Breadcrumbs;
use RuntimeException;
use function redirect;
use function view;

final class InventarioController
{
    private const ESTADOS = ['Nuevo', 'Reparado', 'Dañado'];

    private InventarioRepository $repo;

    public function __construct(?InventarioRepository $repo = null)
    {
        $this->repo = $repo ?? new InventarioRepository();
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : [];
        $items   = $this->repo->list($filters);

        view('contabilidad/inventario/index', [
            'layout'  => 'layout',
            'title'   => 'Inventario de equipos',
            'items'   => $items,
            'estados' => self::ESTADOS,
            'filters' => $filters,
        ]);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make([
            ['href' => '/contabilidad', 'label' => 'Contabilidad'],
            ['href' => '/contabilidad/inventario', 'label' => 'Inventario'],
            ['label' => 'Registrar equipo'],
        ]);

        view('contabilidad/inventario/create', [
            'layout' => 'layout',
            'title'  => 'Registrar equipo',
            'crumbs' => $crumbs,
            'estados'=> self::ESTADOS,
        ]);
    }

    public function store(): void
    {
        $parsed = $this->parseInput($_POST, $_FILES ?? []);
        if ($parsed['errors']) {
            redirect('/contabilidad/inventario/crear?error=validacion');
            return;
        }

        try {
            $this->repo->create($parsed['data']);
        } catch (RuntimeException $e) {
            redirect('/contabilidad/inventario/crear?error=registro');
            return;
        }

        redirect('/contabilidad/inventario');
    }

    public function show(int $id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        try {
            $item = $this->repo->find($id);
        } catch (RuntimeException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => [$e->getMessage()]], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($item === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'errors' => ['Equipo no encontrado']], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['ok' => true, 'item' => $item], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{data:array<string,mixed>,errors:array<int,string>}
     */
    private function parseInput(array $post, array $files): array
    {
        $data = [
            'nombre'               => trim((string)($post['nombre'] ?? '')),
            'descripcion'          => trim((string)($post['descripcion'] ?? '')),
            'codigo'               => trim((string)($post['codigo'] ?? '')),
            'estado'               => trim((string)($post['estado'] ?? '')),
            'responsable'          => trim((string)($post['responsable'] ?? '')),
            'responsable_contacto' => trim((string)($post['responsable_contacto'] ?? '')),
            'fecha_entrega'        => trim((string)($post['fecha_entrega'] ?? '')),
            'comentarios'          => trim((string)($post['comentarios'] ?? '')),
            'serie'                => trim((string)($post['serie'] ?? '')),
            'marca'                => trim((string)($post['marca'] ?? '')),
            'modelo'               => trim((string)($post['modelo'] ?? '')),
        ];

        $errors = [];
        if ($data['nombre'] === '') {
            $errors[] = 'Ingresa el nombre del equipo.';
        }
        if ($data['codigo'] === '') {
            $errors[] = 'Ingresa el código del equipo.';
        }
        if ($data['responsable'] === '') {
            $errors[] = 'Define el responsable que recibe el equipo.';
        }
        if ($data['fecha_entrega'] === '') {
            $errors[] = 'Ingresa la fecha de entrega.';
        }
        if (!in_array($data['estado'], self::ESTADOS, true)) {
            $errors[] = 'El estado seleccionado no es válido.';
        }

        if (isset($files['documento']) && is_array($files['documento']) && ($files['documento']['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_NO_FILE) {
            $upload = $this->handleUpload($files['documento']);
            if ($upload['error'] !== null) {
                $errors[] = $upload['error'];
            } else {
                $data['documento_path'] = $upload['path'];
            }
        } else {
            $data['documento_path'] = null;
        }

        return [
            'data'   => $data,
            'errors' => $errors,
        ];
    }

    /**
     * @param array<string,mixed> $file
     * @return array{path:?string,error:?string}
     */
    private function handleUpload(array $file): array
    {
        if (($file['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'No se pudo cargar el archivo adjunto.'];
        }

        $tmp = $file['tmp_name'] ?? null;
        if ($tmp === null || !is_uploaded_file($tmp)) {
            return ['path' => null, 'error' => 'El archivo adjunto es inválido.'];
        }

        $extension = pathinfo($file['name'] ?? 'documento', PATHINFO_EXTENSION);
        $safeExt   = $extension !== '' ? '.' . strtolower($extension) : '';
        $targetDir = $this->storagePath();
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['path' => null, 'error' => 'No se pudo preparar el directorio de almacenamiento.'];
        }

        $filename = 'equipo_' . time() . '_' . bin2hex(random_bytes(5)) . $safeExt;
        $destination = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmp, $destination)) {
            return ['path' => null, 'error' => 'No se pudo guardar el archivo.'];
        }

        return ['path' => 'contabilidad/inventario/' . $filename, 'error' => null];
    }

    private function storagePath(): string
    {
        return rtrim(__DIR__ . '/../../../storage/contabilidad/inventario', '/');
    }
}
