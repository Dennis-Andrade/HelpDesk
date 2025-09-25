<?php
namespace App\Middleware;

use function \redirect; // importamos el helper global

final class AuthMiddleware implements Middleware
{
    /**
     * Si no hay sesión -> redirige a /login
     * Si hay sesión -> continúa con la cadena ($next)
     */
    public function handle(callable $next)
    {
        if (empty($_SESSION['auth'])) {
            redirect('/login'); // hace exit; por lo que no sigue ejecutando
            return null;        // (por si el helper cambiara en el futuro)
        }
        return $next();
    }
}
