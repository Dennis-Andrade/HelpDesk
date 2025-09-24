<?php
namespace App\Repositories\Auth;

use App\Repositories\UserReaderInterface;
use function Config\db;

final class UserRepository implements UserReaderInterface
{
    public function findByIdentity(string $identity): ?array
    {
        $sql = "
          SELECT u.id_usuario AS id, u.username, u.email, u.activo,
                 u.id_rol, u.password_md5, u.nombre_completo,
                 r.nombre_rol AS rol_nombre
          FROM public.usuarios u
          JOIN public.roles r ON r.id_rol = u.id_rol
          WHERE (LOWER(u.username) = LOWER(:id) OR LOWER(u.email) = LOWER(:id))
          LIMIT 1
        ";
        $st = db()->prepare($sql);
        $st->execute(['id'=>$identity]);
        $row = $st->fetch();
        return $row ?: null;
    }
}
