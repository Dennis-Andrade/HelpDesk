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
     * - ruc: opcional, SOLO dígitos, 10 a 13 caracteres si se proporciona
     * - telefono_fijo: opcional, SOLO dígitos, exactamente 7 si se proporciona
     * - telefono_movil: opcional, SOLO dígitos, exactamente 10 si se proporciona
     * - email: opcional, formato email si se proporciona
     * - provincia_id, canton_id, tipo_entidad, id_segmento: opcionales, enteros o null
     * - servicios: opcional, array de enteros
     *
     * @return array{ok:bool, errors:array<string,string>, data:array<string,mixed>}
     */
    public function validarEntidad(array $in): array
    {
        $digits = static function ($value): ?string {
            $value = preg_replace('/\D+/', '', (string)$value);
            return $value === '' ? null : $value;
        };
        $strOrNull = static function ($value): ?string {
            $value = trim((string)$value);
            return $value === '' ? null : $value;
        };
        $intOrNull = static function ($value): ?int {
            $value = trim((string)$value);
            return $value === '' ? null : (int)$value;
        };

        $tipo = $in['tipo_entidad'] ?? 'cooperativa';
        $data = [
            'id'              => null,
            'nombre'          => trim((string)($in['nombre'] ?? '')),
            'ruc'             => $digits($in['ruc'] ?? $in['nit'] ?? null),
            'telefono'        => null,
            'telefono_fijo_1' => $digits($in['telefono_fijo_1'] ?? $in['telefono_fijo'] ?? null),
            'telefono_movil'  => $digits($in['telefono_movil'] ?? null),
            'email'           => $strOrNull($in['email'] ?? null),
            'provincia_id'    => $intOrNull($in['provincia_id'] ?? ''),
            'canton_id'       => $intOrNull($in['canton_id'] ?? ''),
            'tipo_entidad'    => $strOrNull($tipo),
            'id_segmento'     => $tipo === 'cooperativa' ? $intOrNull($in['id_segmento'] ?? '') : null,
            'notas'           => $strOrNull($in['notas'] ?? null),
            'servicios'       => [],
        ];

        $servicios = array_values(array_unique(array_map('intval', (array)($in['servicios'] ?? []))));
        if (in_array(1, $servicios, true)) {
            $servicios = [1];
        }
        $data['servicios'] = $servicios;

        $errors = [];
        if ($data['nombre'] === '') {
            $errors['nombre'] = 'El nombre es obligatorio';
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if ($data['ruc'] !== null) {
            $len = strlen($data['ruc']);
            if ($len < 10 || $len > 13) {
                $errors['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
            }
        }

        if ($data['telefono_fijo_1'] !== null && strlen($data['telefono_fijo_1']) !== 7) {
            $errors['telefono_fijo_1'] = 'El teléfono fijo debe tener 7 dígitos';
        }

        if ($data['telefono_movil'] !== null && strlen($data['telefono_movil']) !== 10) {
            $errors['telefono_movil'] = 'El celular debe tener 10 dígitos';
        }

        $permitidos = ['cooperativa','mutualista','sujeto_no_financiero','caja_ahorros','casa_valores'];
        if ($data['tipo_entidad'] === null || !in_array($data['tipo_entidad'], $permitidos, true)) {
            $errors['tipo_entidad'] = 'Tipo de entidad inválido';
        }

        if ($data['tipo_entidad'] !== 'cooperativa') {
            $data['id_segmento'] = null;
        } elseif ($data['id_segmento'] === null) {
            $errors['id_segmento'] = 'Debe seleccionar un segmento';
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'data' => $data];
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
