<?php
namespace App\Repositories;

interface UserReaderInterface {
    /** @return array|null {id, username, email, activo, id_rol, password_md5, nombre_completo, rol_nombre} */
    public function findByIdentity(string $identity): ?array; // usuario o email
}
