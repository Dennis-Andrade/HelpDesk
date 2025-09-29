<?php
declare(strict_types=1);

namespace App\Repositories\Auth;

use App\Repositories\BaseRepository;
use App\Repositories\UserReaderInterface;
use RuntimeException;

final class UserRepository extends BaseRepository implements UserReaderInterface
{
    public function findByIdentity(string $identity): ?array
    {
        $sql = '
          SELECT u.id_usuario AS id, u.username, u.email, u.activo,
                 u.id_rol, u.password_md5, u.nombre_completo,
                 r.nombre_rol AS rol_nombre
          FROM public.usuarios u
          JOIN public.roles r ON r.id_rol = u.id_rol
          WHERE (LOWER(u.username) = LOWER(:id) OR LOWER(u.email) = LOWER(:id))
          LIMIT 1
        ';

        try {
            $row = $this->db->fetch($sql, array(':id' => array($identity, \PDO::PARAM_STR)));
        } catch (\Throwable $e) {
            throw new RuntimeException('Error al buscar el usuario.', 0, $e);
        }

        return $row ?: null;
    }
}
