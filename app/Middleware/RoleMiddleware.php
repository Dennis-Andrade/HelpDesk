<?php
namespace App\Middleware;

final class RoleMiddleware implements Middleware
{
    /** @var string[] */
    private $allowed;

    public function __construct(array $allowed) {
        $this->allowed = array_map('strtolower', $allowed);
    }

    public function handle(callable $next) {
        $role = strtolower($_SESSION['auth']['role'] ?? '');
        if (!in_array($role, $this->allowed, true)) {
            http_response_code(403);
            echo 'Acceso denegado';
            return null;
        }
        return $next();
    }
}
