<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (is_string($path) && strpos($path, '/storage/') === 0) {
    serve_storage_file($path);
}

$file = __DIR__ . $path;
if ($path !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}
require __DIR__ . '/index.php';

function serve_storage_file(string $path): void
{
    $relative = ltrim(substr($path, strlen('/storage/')), '/');
    if ($relative === '') {
        http_response_code(404);
        echo 'Archivo no encontrado';
        exit;
    }

    $storageRoot = realpath(__DIR__ . '/../storage');
    if ($storageRoot === false) {
        http_response_code(404);
        echo 'Archivo no encontrado';
        exit;
    }

    $fullPath = $storageRoot . '/' . $relative;
    $realPath = realpath($fullPath);
    if ($realPath === false || strpos($realPath, $storageRoot) !== 0 || !is_file($realPath)) {
        http_response_code(404);
        echo 'Archivo no encontrado';
        exit;
    }

    $mime = mime_content_type($realPath);
    if ($mime === false || $mime === '') {
        $mime = 'application/octet-stream';
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($realPath));
    header('Cache-Control: public, max-age=86400, must-revalidate');
    readfile($realPath);
    exit;
}
