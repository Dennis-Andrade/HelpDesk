<?php
namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\ValidationService;

final class BuscarEntidadesService
{
    /** @var EntidadRepository */
    private $repository;
    /** @var ValidationService */
    private $validator;

    public function __construct(?EntidadRepository $repository = null, ?ValidationService $validator = null)
    {
        $this->repository = $repository ?? new EntidadRepository();
        $this->validator  = $validator ?? new ValidationService();
    }

    /**
     * @return array{items:array<int,array<string,mixed>>, total:int, page:int, perPage:int}
     */
    public function buscar(?string $q, int $page, int $perPage = 12): array
    {
        $term = $q !== null ? trim($q) : null;
        if ($term !== null && $term !== '' && !$this->validator->stringLength($term, 3, 120)) {
            $term = null;
        }

        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1) {
            $perPage = 12;
        } elseif ($perPage > 60) {
            $perPage = 60;
        }

        $offset = ($page - 1) * $perPage;

        $result = $this->repository->search($term, $perPage, $offset);

        if (isset($result['perPage'])) {
            $repoPerPage = (int)$result['perPage'];
            if ($repoPerPage > 0) {
                $perPage = $repoPerPage;
            }
        }
        if (isset($result['page'])) {
            $page = max(1, (int)$result['page']);
        }

        $total = (int)($result['total'] ?? 0);
        $itemsRaw = $result['items'];

        if ($total > 0 && $offset >= $total) {
            $page   = (int)ceil($total / $perPage);
            $page   = $page > 0 ? $page : 1;
            $offset = ($page - 1) * $perPage;
            $result = $this->repository->search($term, $perPage, $offset);
            if (isset($result['perPage'])) {
                $repoPerPage = (int)$result['perPage'];
                if ($repoPerPage > 0) {
                    $perPage = $repoPerPage;
                }
            }
            if (isset($result['page'])) {
                $page = max(1, (int)$result['page']);
            }
            $total    = (int)($result['total'] ?? $total);
            $itemsRaw = $result['items'];
        }

        if ($total === 0) {
            $page = 1;
        }

        $items = array_map(function (array $row): array {
            return $this->mapEntidad($row);
        }, $itemsRaw);

        return array(
            'items'   => $items,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
        );
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapEntidad(array $row): array
    {
        $telefonos = $this->splitList($row['telefono'] ?? null);
        $emails    = $this->splitList($row['email'] ?? null);
        $servicios = $this->splitList($row['servicios_text'] ?? null);

        return array(
            'id'         => isset($row['id']) ? (int)$row['id'] : 0,
            'nombre'     => isset($row['nombre']) ? (string)$row['nombre'] : '',
            'ruc'        => isset($row['ruc']) && $row['ruc'] !== '' ? (string)$row['ruc'] : null,
            'telefono'   => $telefonos,
            'email'      => $emails,
            'provincia'  => isset($row['provincia']) ? (string)$row['provincia'] : null,
            'canton'     => isset($row['canton']) ? (string)$row['canton'] : null,
            'segmento'   => isset($row['segmento']) ? (string)$row['segmento'] : null,
            'servicios'  => $servicios,
        );
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function splitList($value): array
    {
        if ($value === null) {
            return array();
        }

        if (is_array($value)) {
            $list = $value;
        } else {
            $list = explode(',', (string)$value);
        }

        $clean = array();
        foreach ($list as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $trim = trim((string)$item);
            if ($trim === '') {
                continue;
            }
            if (!in_array($trim, $clean, true)) {
                $clean[] = $trim;
            }
        }

        return $clean;
    }
}
