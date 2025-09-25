<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\ValidationService;

final class ActualizarEntidadService
{
    private $repo;
    private $val;
    public function __construct() { $this->repo = new EntidadRepository(); $this->val = new ValidationService(); }

    /** @return array{ok:bool, errors?:array, data?:array} */
    public function actualizar(int $id, array $input): array
    {
        $v = $this->val->validarEntidad($input);
        if (!$v['ok']) return ['ok'=>false, 'errors'=>$v['errors'], 'data'=>$v['data']];
        $ok = $this->repo->update($id, $v['data']);
        return ['ok'=>$ok];
    }
}
