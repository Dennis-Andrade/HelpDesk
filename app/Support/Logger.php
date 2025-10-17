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
            'class'   => get_class($e),
            'msg'     => $e->getMessage(),
        ];

        $pdo = self::extractPdoException($e);
        if ($pdo !== null) {
            $payload['pdo'] = [
                'class'     => get_class($pdo),
                'code'      => $pdo->getCode(),
                'message'   => $pdo->getMessage(),
                'errorInfo' => $pdo->errorInfo ?? null,
            ];
        }

        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private static function extractPdoException(\Throwable $e): ?\PDOException
    {
        $current = $e;
        while ($current !== null) {
            if ($current instanceof \PDOException) {
                return $current;
            }
            $current = $current->getPrevious();
        }

        return null;
    }
}
