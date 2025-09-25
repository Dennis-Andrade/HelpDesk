<?php
namespace App\Services\Auth;

use App\Repositories\Auth\UserRepository;

final class LoginService
{
    private $repo;
    private $hasher;

    public function __construct()
    {
        $this->repo   = new UserRepository();
        $this->hasher = new PasswordHasher();
    }

    /** @return array|null {id,name,email,role} */
    public function attempt(string $identity, string $password): ?array
    {
        $u = $this->repo->findByIdentity($identity);
        if (!$u || !$u['activo']) return null;

        if (!$this->hasher->verify($password, (string)$u['password_md5'])) return null;

        // normalizamos slug de rol
        $role = strtolower((string)$u['rol_nombre']);
        return [
            'id'    => (int)$u['id'],
            'name'  => (string)$u['nombre_completo'],
            'email' => (string)$u['email'],
            'role'  => $role,
        ];
    }
}
