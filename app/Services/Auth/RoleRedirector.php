<?php
namespace App\Services\Auth;

final class RoleRedirector
{
    /** define aquí el mapping centralizado */
    private $map = [
        'administrador' => '/administrador',
        'comercial'     => '/comercial',
        'contabilidad'  => '/contabilidad',
        'sistemas'      => '/sistemas',
        'cumplimiento'  => '/cumplimiento',
        'providencias'  => '/providencias', // si existe (en tu dump hay) -> ajusta si quieres otro landing
    ];

    public function pathFor(?string $role): string
    {
        return $this->map[$role ?? ''] ?? '/comercial'; // default
    }
}
