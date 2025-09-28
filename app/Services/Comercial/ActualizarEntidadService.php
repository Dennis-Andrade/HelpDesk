<?php
declare(strict_types=1);

namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\ValidationService;
use Config\Cnxn;

final class ActualizarEntidadService
{
    private EntidadRepository $repo;
    private ValidationService $val;

    public function __construct(?EntidadRepository $repo = null, ?ValidationService $val = null)
    {
        $this->repo = $repo ?? new EntidadRepository(Cnxn::pdo());
        $this->val  = $val ?? new ValidationService();
    }

    /** @return array{ok:bool, errors?:array, data?:array} */
    public function actualizar(int $id, array $input): array
    {
        $v = $this->val->validarEntidad($input);
        if (!$v['ok']) {
            return ['ok' => false, 'errors' => $v['errors'], 'data' => $v['data']];
        }
        $this->repo->update($id, $v['data']);
        return ['ok' => true];
    }
}
