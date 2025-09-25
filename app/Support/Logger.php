<?php
declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public static function error(\Throwable $e, string $context = ''): void
    {
        $dir = __DIR__ . '/../../storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/app.log';
        $payload = [
            'dt'      => date('c'),
            'context' => $context,
            'msg'     => $e->getMessage(),
        ];

        if ($e instanceof \PDOException && isset($e->errorInfo)) {
            $payload['errorInfo'] = $e->errorInfo;
        }

        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }
}
