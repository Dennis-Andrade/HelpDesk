<?php
declare(strict_types=1);

namespace App\Services\Comercial;

use App\Repositories\Comercial\EntidadQueryRepository;
use App\Services\Shared\ValidationService;
use Config\Cnxn;

final class BuscarEntidadesService
{
    private EntidadQueryRepository $repository;
    private ValidationService $validator;

    public function __construct(?EntidadQueryRepository $repository = null, ?ValidationService $validator = null)
    {
        $this->repository = $repository ?? new EntidadQueryRepository(Cnxn::pdo());
        $this->validator  = $validator ?? new ValidationService();
    }

    /**
     * @return array{items:array, total:int, page:int, perPage:int}
     */
    public function buscar(string $q, int $page, int $perPage = 10): array
    {
        $term = trim($q);
        if ($term !== '' && !$this->validator->stringLength($term, 3, 120)) {
            $term = '';
        }

        if ($page < 1) {
            $page = 1;
        }

        if ($perPage < 1) {
            $perPage = 10;
        } elseif ($perPage > 60) {
            $perPage = 60;
        }

        $result = $this->repository->search($term, $page, $perPage);

        $items = array_map(function (array $row): array {
            return $this->mapEntidad($row);
        }, $result['items']);

        return [
            'items'   => $items,
            'total'   => (int)($result['total'] ?? 0),
            'page'    => (int)($result['page'] ?? $page),
            'perPage' => (int)($result['perPage'] ?? $perPage),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapEntidad(array $row): array
    {
        $telefonos = $this->sanitizeList($row['telefonos'] ?? []);
        $emails    = $this->sanitizeList($row['emails'] ?? []);
        $servicios = $this->sanitizeList($row['servicios'] ?? []);

        $serviciosCount = isset($row['servicios_count']) ? (int)$row['servicios_count'] : count($servicios);

        return [
            'id'               => isset($row['id']) ? (int)$row['id'] : (int)($row['id_entidad'] ?? 0),
            'nombre'           => isset($row['nombre']) ? (string)$row['nombre'] : '',
            'ruc'              => isset($row['ruc']) && $row['ruc'] !== '' ? (string)$row['ruc'] : null,
            'segmento_nombre'  => isset($row['segmento_nombre']) ? (string)$row['segmento_nombre'] : null,
            'provincia_nombre' => isset($row['provincia_nombre']) ? (string)$row['provincia_nombre'] : null,
            'canton_nombre'    => isset($row['canton_nombre']) ? (string)$row['canton_nombre'] : null,
            'telefonos'        => $telefonos,
            'emails'           => $emails,
            'servicios'        => $servicios,
            'servicios_count'  => $serviciosCount,
            'tipo_entidad'     => isset($row['tipo_entidad']) ? (string)$row['tipo_entidad'] : null,
            'id_segmento'      => isset($row['id_segmento']) ? (int)$row['id_segmento'] : null,
            'provincia_id'     => isset($row['provincia_id']) && $row['provincia_id'] !== null ? (int)$row['provincia_id'] : null,
            'canton_id'        => isset($row['canton_id']) && $row['canton_id'] !== null ? (int)$row['canton_id'] : null,
        ];
    }

    /**
     * @param mixed $values
     * @return array<int,string>
     */
    private function sanitizeList($values): array
    {
        if (is_string($values)) {
            $jsonDecoded = json_decode($values, true);
            if (is_array($jsonDecoded)) {
                $values = $jsonDecoded;
            }
        }

        if (!is_array($values)) {
            if ($values === null || $values === '') {
                return [];
            }
            $values = [(string)$values];
        }

        $clean = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $trimmed = trim((string)$value);
            if ($trimmed === '') {
                continue;
            }
            if (!in_array($trimmed, $clean, true)) {
                $clean[] = $trimmed;
            }
        }

        return $clean;
    }
}
