<?php
declare(strict_types=1);

/**
 * config/cnxn.php
 * - Conexión PDO centralizada para PostgreSQL 14.
 * - PHP 7.x compatible, sin Composer.
 * - Usa variables de entorno si existen (PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD, APP_TZ, APP_NAME).
 * - Ajusta `application_name`, `Time Zone` y `search_path` (public).
 */

namespace Config;

final class Cnxn
{
    /** @var \PDO|null */
    private static $pdo = null;

    public static function pdo(): \PDO
    {
        if (self::$pdo instanceof \PDO) {
            return self::$pdo;
        }

        $host = getenv('PGHOST')     ?: '127.0.0.1';
        $port = getenv('PGPORT')     ?: '5434';
        $db   = getenv('PGDATABASE') ?: 'helpdesk';
        $user = getenv('PGUSER')     ?: 'postgres';
        $pass = getenv('PGPASSWORD') ?: '12345';

        $dsn  = "pgsql:host={$host};port={$port};dbname={$db}";

        $pdo = new \PDO($dsn, $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
        ]);

        // Session settings por conexión
        $tz      = getenv('APP_TZ')   ?: 'UTC';
        $appName = getenv('APP_NAME') ?: 'helpdesk-php7';

        // application_name y zona horaria
        $pdo->exec("SET application_name TO " . $pdo->quote($appName));
        $pdo->exec("SET TIME ZONE " . $pdo->quote($tz));

        // Tu dump usa schema PUBLIC: fijamos search_path para evitar calificar cada tabla
        // (ej.: public.cooperativas) -> search_path=public
        $pdo->exec("SET search_path TO public");

        // Opcional: asegura UTF8 (normalmente viene desde client_encoding del dump)
        $pdo->exec("SET client_encoding TO 'UTF8'");

        self::$pdo = $pdo;
        return self::$pdo;
    }
}

/**
 * Helper global: db()
 * Uso: $pdo = \Config\db();
 */
function db(): \PDO
{
    return Cnxn::pdo();
}
