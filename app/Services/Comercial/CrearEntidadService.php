<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\ValidationService;

final class CrearEntidadService
{
    private $repo;
    private $val;
    public function __construct() { $this->repo = new EntidadRepository(); $this->val = new ValidationService(); }

    /** @return array{ok:bool, id?:int, errors?:array, data?:array} */
    public function crear(array $input): array
    {
        $v = $this->val->validarEntidad($input);
        if (!$v['ok']) return ['ok'=>false, 'errors'=>$v['errors'], 'data'=>$v['data']];
        $id = $this->repo->create($v['data']);
        return ['ok'=>true, 'id'=>$id];
    }
}
