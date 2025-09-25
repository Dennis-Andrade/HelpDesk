<?php
namespace App\Controllers\Auth;

use App\Services\Auth\LoginService;
use App\Services\Auth\RoleRedirector;

use function \view;
use function \redirect;

final class LoginController
{
    private $login;
    private $redirector;

    public function __construct()
    {
        $this->login = new LoginService();
        $this->redirector = new RoleRedirector();
    }

    public function show(): void
    {
        if (!empty($_SESSION['auth'])) {
            $this->goHomeByRole($_SESSION['auth']['role'] ?? null);
            return;
        }
        \view('auth/login', ['title'=>'Ingresar', 'layout'=>'auth']);
    }

    public function login(): void
    {
        $id = trim($_POST['id'] ?? '');
        $pw = (string)($_POST['password'] ?? '');

        $user = $this->login->attempt($id, $pw);
        if (!$user) {
            \view('auth/login', [
                'title'=>'Ingresar',
                'error'=>'Credenciales inválidas o usuario inactivo.',
                'layout'=>'auth'
            ]);
            return;
        }

        $_SESSION['auth'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
        $this->goHomeByRole($user['role']);
    }

    public function home(): void
    {
        if (!empty($_SESSION['auth'])) {
            $this->goHomeByRole($_SESSION['auth']['role']);
        } else {
            \redirect('/login');
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        \redirect('/login');
    }

    private function goHomeByRole(?string $role): void
    {
        $path = $this->redirector->pathFor($role);
        \redirect($path);
    }
}
