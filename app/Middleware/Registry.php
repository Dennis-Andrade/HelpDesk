<?php
namespace App\Middleware;

final class Registry
{
    public static function make(string $name): Middleware
    {
        if ($name === 'auth') return new AuthMiddleware();

        if (strpos($name, 'role:') === 0) {
            $csv = substr($name, 5);
            $roles = array_filter(array_map('trim', explode(',', $csv)));
            return new RoleMiddleware($roles);
        }

        throw new \InvalidArgumentException("Middleware desconocido: {$name}");
    }
}
