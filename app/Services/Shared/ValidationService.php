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
        // Helpers
        $digits = static fn($s) => preg_replace('/\D+/', '', (string)$s);
        $intOrNull = static function($v) {
            if ($v === null) {
                return null;
            }
            if ($v === '' || $v === false) {
                return null;
            }
            if (is_int($v)) {
                return $v;
            }
            if (is_numeric($v)) {
                return (int)$v;
            }
            return null;
        };

        $trimOrNull = static function($value): ?string {
            if ($value === null) {
                return null;
            }
            $trimmed = trim((string)$value);
            return $trimmed === '' ? null : $trimmed;
        };

        $nombre = $trimOrNull($in['nombre'] ?? '');
        $rucDigits = $digits($in['nit'] ?? $in['ruc'] ?? '');
        $telefonoFijo = $digits($in['telefono_fijo'] ?? $in['tfijo'] ?? '');
        $telefonoMovil = $digits($in['telefono_movil'] ?? $in['tmov'] ?? '');
        $telefonoLegacy = $digits($in['telefono'] ?? '');
        $email = $trimOrNull($in['email'] ?? '');
        $tipoEntidad = $trimOrNull($in['tipo_entidad'] ?? 'cooperativa') ?? 'cooperativa';
        $notas = $trimOrNull($in['notas'] ?? '');

        $permitidos = ['cooperativa','mutualista','sujeto_no_financiero','caja_ahorros','casa_valores'];
        if (!in_array($tipoEntidad, $permitidos, true)) {
            $tipoEntidad = 'cooperativa';
        }

        $segmento = $intOrNull($in['id_segmento'] ?? null);
        if ($tipoEntidad !== 'cooperativa') {
            $segmento = null;
        }

        $servicios = $in['servicios'] ?? [];
        if (!is_array($servicios)) {
            $servicios = [];
        }
        $servicios = array_values(array_unique(array_map(
            static fn($value): int => (int)$value,
            array_filter($servicios, static fn($value): bool => is_numeric($value))
        )));
        $servicios = array_values(array_filter($servicios, static fn(int $value): bool => $value > 0));
        if (in_array(1, $servicios, true)) {
            $servicios = [1];
        }

        $data = [
            'nombre'           => (string)($nombre ?? ''),
            'ruc'              => $rucDigits === '' ? null : $rucDigits,
            'telefono'         => $telefonoLegacy === '' ? null : $telefonoLegacy,
            'telefono_fijo_1'  => $telefonoFijo === '' ? null : $telefonoFijo,
            'telefono_movil'   => $telefonoMovil === '' ? null : $telefonoMovil,
            'email'            => $email !== null ? strtolower($email) : null,
            'provincia_id'     => $intOrNull($in['provincia_id'] ?? null),
            'canton_id'        => $intOrNull($in['canton_id'] ?? null),
            'tipo_entidad'     => $tipoEntidad,
            'id_segmento'      => $segmento,
            'notas'            => $notas,
            'servicios'        => $servicios,
        ];

        if (isset($in['id'])) {
            $data['id'] = $intOrNull($in['id']);
        }

        // Alias para reusar valores en los formularios (legacy)
        $data['nit'] = $data['ruc'];

        $errors = [];

        if ($nombre === null || $nombre === '') {
            $errors['nombre'] = 'El nombre es obligatorio';
        }

        if ($data['ruc'] !== null) {
            $length = strlen($data['ruc']);
            if ($length < 10 || $length > 13) {
                $errors['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
            }
        }

        if ($data['telefono_fijo_1'] !== null && strlen($data['telefono_fijo_1']) !== 7) {
            $errors['telefono_fijo'] = 'El teléfono fijo debe tener 7 dígitos';
        }

        if ($data['telefono_movil'] !== null && strlen($data['telefono_movil']) !== 10) {
            $errors['telefono_movil'] = 'El celular debe tener 10 dígitos';
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if ($tipoEntidad === 'cooperativa' && $segmento === null) {
            $errors['id_segmento'] = 'Seleccione un segmento';
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
