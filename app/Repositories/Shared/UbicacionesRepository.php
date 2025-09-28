<?php
declare(strict_types=1);

namespace App\Repositories\Shared;

use PDO;

final class UbicacionesRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public function provincias(): array
    {
        $sql = "SELECT id_provincia AS id, nombre FROM public.provincias ORDER BY nombre";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int, array{id:int, nombre:string}> */
    public function cantones(int $provinciaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id_canton AS id, nombre FROM public.cantones
             WHERE provincia_id = :pid ORDER BY nombre"
        );
        $stmt->execute([':pid' => $provinciaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
