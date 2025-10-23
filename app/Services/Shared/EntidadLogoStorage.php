<?php
declare(strict_types=1);

namespace App\Services\Shared;

final class EntidadLogoStorage
{
    private const MAX_FILE_SIZE = 1048576; // 1 MB

    /** @var array<string,string> */
    private const ALLOWED_MIME = [
        'image/png'  => 'png',
        'image/jpeg' => 'jpg',
        'image/jpg'  => 'jpg',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /**
     * @param array<string,mixed> $file
     * @return array{path:?string,error:?string}
     */
    public function store(array $file): array
    {
        $errorCode = isset($file['error']) ? (int)$file['error'] : \UPLOAD_ERR_NO_FILE;
        if ($errorCode !== \UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => $this->translateUploadError($errorCode)];
        }

        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['path' => null, 'error' => 'La imagen subida no es válida.'];
        }

        $size = isset($file['size']) ? (int)$file['size'] : 0;
        if ($size > self::MAX_FILE_SIZE) {
            return ['path' => null, 'error' => 'La imagen supera el tamaño máximo de 1 MB.'];
        }

        $info = @getimagesize($tmp);
        if ($info === false || !isset($info['mime'])) {
            return ['path' => null, 'error' => 'El archivo debe ser una imagen válida.'];
        }

        $mime = (string)$info['mime'];
        if (!isset(self::ALLOWED_MIME[$mime])) {
            return ['path' => null, 'error' => 'Formato de imagen no permitido. Usa PNG, JPG, WEBP o GIF.'];
        }

        $directory = $this->storageDir();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return ['path' => null, 'error' => 'No se pudo preparar el directorio de almacenamiento.'];
        }

        $filename = $this->buildFilename(self::ALLOWED_MIME[$mime]);
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file($tmp, $destination)) {
            return ['path' => null, 'error' => 'No se pudo guardar la imagen.'];
        }

        return ['path' => $this->relativePath($filename), 'error' => null];
    }

    public function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $fullPath = $this->baseStoragePath() . '/' . ltrim($relativePath, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function storageDir(): string
    {
        return $this->baseStoragePath() . '/entidades/logos';
    }

    private function baseStoragePath(): string
    {
        return rtrim(dirname(__DIR__, 3) . '/storage', '/');
    }

    private function relativePath(string $filename): string
    {
        return 'entidades/logos/' . $filename;
    }

    private function buildFilename(string $extension): string
    {
        $suffix = $this->randomSuffix();
        return 'entidad_logo_' . date('YmdHis') . '_' . $suffix . '.' . $extension;
    }

    private function randomSuffix(): string
    {
        try {
            return bin2hex(random_bytes(5));
        } catch (\Throwable $e) {
            return substr(hash('sha256', (string)microtime(true) . (string)mt_rand()), 0, 10);
        }
    }

    private function translateUploadError(int $code): ?string
    {
        switch ($code) {
            case \UPLOAD_ERR_OK:
                return null;
            case \UPLOAD_ERR_NO_FILE:
                return null;
            case \UPLOAD_ERR_INI_SIZE:
            case \UPLOAD_ERR_FORM_SIZE:
                return 'La imagen excede el tamaño permitido.';
            case \UPLOAD_ERR_PARTIAL:
                return 'La imagen se subió de forma incompleta. Inténtalo de nuevo.';
            case \UPLOAD_ERR_NO_TMP_DIR:
            case \UPLOAD_ERR_CANT_WRITE:
            case \UPLOAD_ERR_EXTENSION:
                return 'No se pudo cargar la imagen por un error interno del servidor.';
            default:
                return 'No se pudo cargar la imagen.';
        }
    }
}
