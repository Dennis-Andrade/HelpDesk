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
            'telefono'         => isset($telefonos[0]) ? $telefonos[0] : null,
            'emails'           => $emails,
            'email'            => isset($emails[0]) ? $emails[0] : null,
            'servicios'        => $servicios,
            'servicios_count'  => $serviciosCount,
            'servicios_text'   => isset($row['servicios_text']) ? (string)$row['servicios_text'] : null,
            'tipo_entidad'     => isset($row['tipo_entidad']) ? (string)$row['tipo_entidad'] : null,
            'id_segmento'      => isset($row['id_segmento']) ? (int)$row['id_segmento'] : null,
            'provincia_id'     => isset($row['provincia_id']) && $row['provincia_id'] !== null ? (int)$row['provincia_id'] : null,
            'canton_id'        => isset($row['canton_id']) && $row['canton_id'] !== null ? (int)$row['canton_id'] : null,
            'servicio_activo'  => isset($row['servicio_activo']) ? (string)$row['servicio_activo'] : null,
            'logo_path'        => isset($row['logo_path']) && trim((string)$row['logo_path']) !== '' ? trim((string)$row['logo_path']) : null,
            'direccion_calle'  => $this->stringOrNull($row['direccion_calle'] ?? null),
            'direccion_interseccion' => $this->stringOrNull($row['direccion_interseccion'] ?? null),
            'direccion_facturacion'  => $this->stringOrNull($row['direccion_facturacion'] ?? null),
            'fecha_registro'   => $this->stringOrNull($row['fecha_registro'] ?? null),
            'facturacion_total'=> isset($row['facturacion_total']) ? (float)$row['facturacion_total'] : 0.0,
            'sic_licencias'    => isset($row['sic_licencias']) ? (int)$row['sic_licencias'] : 0,
        ];
    }

    /**
     * @param mixed $values
     * @return array<int,string>
     */
    private function sanitizeList($values): array
    {
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

    /**
     * @param mixed $value
     */
    private function stringOrNull($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}
