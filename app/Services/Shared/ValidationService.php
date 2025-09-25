<?php
declare(strict_types=1);

namespace App\Services\Shared;

use DateTimeImmutable;
use DateTimeInterface;

final class ValidationService
{
    /**
     * Valida y normaliza los datos de la Entidad (cooperativa).
     * - nombre: obligatorio
     * - nit/ruc: opcional, 10 a 13 dígitos
     * - telefono_fijo: opcional, 7 dígitos
     * - telefono_movil: opcional, 10 dígitos
     * - email: opcional, formato válido
     * - provincia_id, canton_id, id_segmento: enteros o null
     * - tipo_entidad: cooperativa/mutualista/sujeto_no_financiero/caja_ahorros/casa_valores
     * - servicios: array de enteros positivos
     *
     * @return array{ok:bool, errors:array<string,string>, data:array<string,mixed>}
     */
    public function validarEntidad(array $in): array
    {
        $digits = static function ($value): string {
            return preg_replace('/\D+/', '', (string)$value);
        };

        $intOrNull = static function ($value): ?int {
            if ($value === null || $value === '') {
                return null;
            }
            if (is_int($value)) {
                return $value;
            }
            if (is_numeric($value)) {
                return (int)$value;
            }
            return null;
        };

        $tipoRaw = trim((string)($in['tipo_entidad'] ?? $in['tipo'] ?? ''));
        $tiposPermitidos = array('cooperativa', 'mutualista', 'sujeto_no_financiero', 'caja_ahorros', 'casa_valores');
        $tipo = $tipoRaw !== '' && in_array($tipoRaw, $tiposPermitidos, true) ? $tipoRaw : 'cooperativa';

        $segmentoRaw = $in['id_segmento'] ?? $in['segmento'] ?? null;
        $segmentoId = $intOrNull($segmentoRaw);

        $telefonoFijo = $digits($in['telefono_fijo'] ?? $in['telefono'] ?? $in['telefono_principal'] ?? '');
        $telefonoMovil = $digits($in['telefono_movil'] ?? $in['celular'] ?? '');

        $servicios = $in['servicios'] ?? array();
        if (!is_array($servicios)) {
            $servicios = array();
        }
        $servicios = array_values(array_unique(array_filter(array_map(static function ($value) {
            if (is_int($value)) {
                return $value > 0 ? $value : null;
            }
            if (is_numeric($value)) {
                $num = (int)$value;
                return $num > 0 ? $num : null;
            }
            return null;
        }, $servicios))));

        $data = array(
            'nombre'          => trim((string)($in['nombre'] ?? '')),
            'nit'             => $digits($in['nit'] ?? $in['ruc'] ?? ''),
            'ruc'             => $digits($in['ruc'] ?? $in['nit'] ?? ''),
            'telefono_fijo'   => $telefonoFijo,
            'telefono_movil'  => $telefonoMovil,
            'email'           => trim((string)($in['email'] ?? '')),
            'provincia_id'    => $intOrNull($in['provincia_id'] ?? null),
            'canton_id'       => $intOrNull($in['canton_id'] ?? null),
            'tipo_entidad'    => $tipo,
            'id_segmento'     => $segmentoId,
            'segmento'        => $segmentoId !== null ? (string)$segmentoId : '',
            'notas'           => trim((string)($in['notas'] ?? '')),
            'servicios'       => $servicios,
            'activa'          => isset($in['activa']) ? (bool)$in['activa'] : true,
            'servicio_activo' => isset($in['servicio_activo']) ? (bool)$in['servicio_activo'] : null,
        );

        // Compatibilidad con formularios existentes
        $data['telefono'] = $data['telefono_fijo'] !== '' ? $data['telefono_fijo'] : $data['telefono_movil'];

        $errors = array();

        if ($data['nombre'] === '') {
            $errors['nombre'] = 'El nombre es obligatorio';
        }

        if ($data['nit'] !== '' && (strlen($data['nit']) < 10 || strlen($data['nit']) > 13)) {
            $errors['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
        }

        if ($data['telefono_fijo'] !== '' && strlen($data['telefono_fijo']) !== 7) {
            $errors['telefono_fijo'] = 'El teléfono fijo debe tener 7 dígitos';
        }

        if ($data['telefono_movil'] !== '' && strlen($data['telefono_movil']) !== 10) {
            $errors['telefono_movil'] = 'El celular debe tener 10 dígitos';
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if ($segmentoRaw !== null && $segmentoRaw !== '' && $segmentoId === null) {
            $errors['segmento'] = 'El segmento debe ser un número entero';
        }

        return array('ok' => empty($errors), 'errors' => $errors, 'data' => $data);
    }

    public function stringLength(string $value, int $min, int $max): bool
    {
        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }

    public function regex(string $value, string $pattern): bool
    {
        return (bool)preg_match($pattern, $value);
    }

    public function dateTimeValid(string $value, string $format = DateTimeInterface::ATOM): bool
    {
        $dt = DateTimeImmutable::createFromFormat($format, $value);
        if ($dt === false) {
            return false;
        }

        return $dt->format($format) === $value;
    }

    public function dateTimeFuture(string $value, string $format = DateTimeInterface::ATOM): bool
    {
        if (!$this->dateTimeValid($value, $format)) {
            return false;
        }

        $dt = DateTimeImmutable::createFromFormat($format, $value);
        if ($dt === false) {
            return false;
        }

        return $dt > new DateTimeImmutable();
    }

    public function digits(string $value, int $minLength, int $maxLength): bool
    {
        if ($value === '') {
            return false;
        }

        if (!preg_match('/^\d+$/', $value)) {
            return false;
        }

        $length = strlen($value);

        return $length >= $minLength && $length <= $maxLength;
    }
}
