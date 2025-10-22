<?php
declare(strict_types=1);

namespace App\Controllers\Contabilidad;

use App\Repositories\Contabilidad\HistorialRepository;
use App\Support\Logger;
use function bin2hex;
use function json_encode;
use function is_dir;
use function is_uploaded_file;
use function mkdir;
use function move_uploaded_file;
use function pathinfo;
use function random_bytes;
use function realpath;
use function str_replace;
use function trim;

final class HistorialController
{
    private const IVA_RATE = 0.15;

    private HistorialRepository $historial;

    public function __construct(?HistorialRepository $historial = null)
    {
        $this->historial = $historial ?? new HistorialRepository();
    }

    public function list(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $contratoId = isset($_GET['contrato']) ? (int)$_GET['contrato'] : 0;
        if ($contratoId <= 0) {
            echo json_encode(['ok' => true, 'items' => []]);
            return;
        }

        try {
            $items = $this->historial->listByContrato($contratoId);
        } catch (\Throwable $e) {
            Logger::error($e, 'HistorialController::list');
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => ['No se pudo obtener el historial.']]);
            return;
        }

        echo json_encode(['ok' => true, 'items' => $items]);
    }

    public function store(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $parsed = $this->parseInput($_POST, $_FILES ?? []);
        if ($parsed['errors']) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'errors' => $parsed['errors']]);
            return;
        }

        try {
            $newId = $this->historial->create($parsed['data']);
            $item  = $this->historial->find($newId);
        } catch (\Throwable $e) {
            Logger::error($e, 'HistorialController::store');
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => ['No se pudo guardar el pago.']]);
            return;
        }

        echo json_encode(['ok' => true, 'item' => $item]);
    }

    public function delete($id): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'errors' => ['Registro inválido.']]);
            return;
        }

        try {
            $this->historial->delete($id);
        } catch (\Throwable $e) {
            Logger::error($e, 'HistorialController::delete');
            http_response_code(500);
            echo json_encode(['ok' => false, 'errors' => ['No se pudo eliminar el registro.']]);
            return;
        }

        echo json_encode(['ok' => true]);
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{data:array<string,mixed>,errors:array<string,string>}
     */
    private function parseInput(array $post, array $files): array
    {
        $data = [
            'id_cooperativa'  => isset($post['id_cooperativa']) ? (int)$post['id_cooperativa'] : 0,
            'id_contratacion' => isset($post['id_contratacion']) && $post['id_contratacion'] !== '' ? (int)$post['id_contratacion'] : null,
            'periodo'         => trim((string)($post['periodo'] ?? '')),
            'fecha_emision'   => trim((string)($post['fecha_emision'] ?? '')),
            'fecha_vencimiento'=> trim((string)($post['fecha_vencimiento'] ?? '')),
            'fecha_pago'      => trim((string)($post['fecha_pago'] ?? '')),
            'monto_base'      => $this->toFloat($post['monto_base'] ?? 0),
            'monto_iva'       => $this->toFloat($post['monto_iva'] ?? 0),
            'monto_total'     => $this->toFloat($post['monto_total'] ?? 0),
            'estado'          => trim((string)($post['estado'] ?? 'pendiente')),
            'observaciones'   => trim((string)($post['observaciones'] ?? '')),
        ];

        $errors = [];
        if ($data['id_cooperativa'] <= 0) {
            $errors['id_cooperativa'] = 'Selecciona la entidad.';
        }
        if ($data['periodo'] === '') {
            $errors['periodo'] = 'Indica el periodo o referencia.';
        }
        if ($data['fecha_emision'] === '') {
            $errors['fecha_emision'] = 'Define la fecha de emisión.';
        }
        if ($data['monto_base'] <= 0) {
            $errors['monto_base'] = 'Ingresa un monto base válido.';
        }

        if ($data['monto_iva'] === 0.0) {
            $data['monto_iva'] = round($data['monto_base'] * self::IVA_RATE, 2);
        }
        if ($data['monto_total'] === 0.0) {
            $data['monto_total'] = round($data['monto_base'] + $data['monto_iva'], 2);
        }

        $upload = null;
        if (isset($files['comprobante']) && is_array($files['comprobante']) && ($files['comprobante']['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_NO_FILE) {
            $upload = $this->handleUpload($files['comprobante']);
            if ($upload['error'] !== null) {
                $errors['comprobante'] = $upload['error'];
            }
        }
        if ($upload && $upload['path'] !== null) {
            $data['comprobante_path'] = $upload['path'];
        }

        return ['data' => $data, 'errors' => $errors];
    }

    /**
     * @param array<string,mixed> $file
     * @return array{path:?string,error:?string}
     */
    private function handleUpload(array $file): array
    {
        if (($file['error'] ?? \UPLOAD_ERR_NO_FILE) !== \UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'No se pudo cargar el archivo.'];
        }

        $tmpName = $file['tmp_name'] ?? null;
        if ($tmpName === null || !is_uploaded_file($tmpName)) {
            return ['path' => null, 'error' => 'Archivo inválido.'];
        }

        $ext = strtolower(pathinfo($file['name'] ?? 'comprobante', PATHINFO_EXTENSION));
        $safeExt = $ext !== '' ? '.' . $ext : '';
        $targetDir = $this->storagePath();
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return ['path' => null, 'error' => 'No se pudo preparar el directorio de archivos.'];
        }

        $fileName = 'historial_' . bin2hex(random_bytes(6)) . $safeExt;
        $destination = $targetDir . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['path' => null, 'error' => 'No se pudo almacenar el comprobante.'];
        }

        return ['path' => $this->relativeStoragePath($destination), 'error' => null];
    }

    private function storagePath(): string
    {
        return rtrim(__DIR__ . '/../../../storage/contabilidad/historial', '/');
    }

    private function relativeStoragePath(string $absolute): string
    {
        $root = realpath(__DIR__ . '/../../../storage');
        $real = realpath($absolute) ?: $absolute;
        if ($root && strpos($real, $root) === 0) {
            return ltrim(str_replace('\\', '/', substr($real, strlen($root))), '/');
        }
        return $real;
    }

    private function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        return (float)str_replace(',', '.', (string)$value);
    }
}
