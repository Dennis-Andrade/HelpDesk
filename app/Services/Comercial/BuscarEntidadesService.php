<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\ValidationService;

final class BuscarEntidadesService
{
    private EntidadRepository $repository;
    private ValidationService $validator;

    public function __construct(?EntidadRepository $repository = null, ?ValidationService $validator = null)
    {
        $this->repository = $repository ?? new EntidadRepository();
        $this->validator  = $validator ?? new ValidationService();
    }

    /**
     * @return array{items:array, total:int, page:int, perPage:int}
     */
    public function buscar(string $q, int $page, int $perPage = 15): array
    {
        $term = trim($q);
        if ($term !== '' && !$this->validator->stringLength($term, 3, 120)) {
            $term = '';
        }

        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1) {
            $perPage = 15;
        } elseif ($perPage > 60) {
            $perPage = 60;
        }

        return $this->repository->search($term, $page, $perPage);
    }
}
