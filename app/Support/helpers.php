<?php
declare(strict_types=1);

/**
 * Helpers globales reutilizables en toda la app.
 * PHP 7.4 compatible.
 */

if (!function_exists('view')) {
    function view(string $tpl, array $data = []): void {
        extract($data);
        $appDir  = dirname(__DIR__); // .../app
        $file    = $appDir . '/Views/' . $tpl . '.php';
        $layout  = isset($data['layout']) ? (string)$data['layout'] : 'layout';
        $layoutF = $appDir . '/Views/layouts/' . $layout . '.php';

        if (!is_file($file))   { http_response_code(500); echo "Vista no encontrada: {$tpl}"; return; }
        if (!is_file($layoutF)){ http_response_code(500); echo "Layout no encontrado: {$layout}"; return; }

        $___viewFile = $file;
        include $layoutF;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $to): void {
        header('Location: ' . $to, true, 302);
        exit;
    }
}
// --- CSRF helpers ---
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        if (empty($_SESSION['_csrf'])) { $_SESSION['_csrf'] = bin2hex(random_bytes(16)); }
        return $_SESSION['_csrf'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): void {
        echo '<input type="hidden" name="_csrf" value="'.htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8').'">';
    }
}
if (!function_exists('csrf_verify')) {
    function csrf_verify(?string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
        return isset($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
    }
}
