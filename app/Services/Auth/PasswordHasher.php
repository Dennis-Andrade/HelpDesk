<?php
namespace App\Services\Auth;

final class PasswordHasher
{
    public function verify(string $plain, string $stored): bool
    {
        // Caso legacy MD5 (32 hex)
        if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            return md5($plain) === strtolower($stored);
        }
        // Bcrypt (futuro)
        if (strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0) {
            return password_verify($plain, $stored);
        }
        // Fallback: no reconocido
        return false;
    }
}
