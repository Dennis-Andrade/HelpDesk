<?php
declare(strict_types=1);

namespace App\Services\Shared;

use DateTimeImmutable;
use DateTimeInterface;

final class ValidationService
{
    /**
     * Valida y normaliza los datos de una entidad financiera.
     *
     * @return array{ok:bool,errors:array<string,string>,data:array<string,mixed>}
     */
    public function validarEntidad(array $in): array
    {
        $digits = static function ($value): string {
            $filtered = preg_replace('/\D+/', '', (string)$value);
            return $filtered ?? '';
        };

        $intOrNull = static function ($value): ?int {
            if ($value === '' || $value === null) {
                return null;
            }
            if (is_numeric($value)) {
                return (int)$value;
            }
            return null;
        };

        $email = trim((string)($in['email'] ?? ''));
        $tipoEntidad = trim((string)($in['tipo_entidad'] ?? 'cooperativa'));

        $data = [
            'nombre'          => trim((string)($in['nombre'] ?? '')),
            'ruc'             => $digits($in['nit'] ?? $in['ruc'] ?? ''),
            'telefono_fijo'   => $digits($in['telefono_fijo'] ?? $in['tfijo'] ?? ''),
            'telefono_movil'  => $digits($in['telefono_movil'] ?? $in['tmov'] ?? ''),
            'email'           => $email,
            'provincia_id'    => $intOrNull($in['provincia_id'] ?? null),
            'canton_id'       => $intOrNull($in['canton_id'] ?? null),
            'tipo_entidad'    => $tipoEntidad !== '' ? $tipoEntidad : 'cooperativa',
            'id_segmento'     => $intOrNull($in['id_segmento'] ?? null),
            'notas'           => trim((string)($in['notas'] ?? '')),
            'direccion_calle' => trim((string)($in['direccion_calle'] ?? $in['calle_principal'] ?? '')),
            'direccion_interseccion' => trim((string)($in['direccion_interseccion'] ?? $in['interseccion'] ?? '')),
        ];

        // Alias para repositorios que esperan la clave 'nit'.
        $data['nit'] = $data['ruc'];

        $servicios = $in['servicios'] ?? [];
        if (!is_array($servicios)) {
            $servicios = [];
        }
        $data['servicios'] = array_values(array_unique(array_map(
            static fn($value) => (int)$value,
            array_filter($servicios, static fn($value) => is_numeric($value))
        )));

        $errors = [];

        if ($data['nombre'] === '') {
            $errors['nombre'] = 'El nombre es obligatorio';
        }

        if ($data['ruc'] !== '' && (strlen($data['ruc']) < 10 || strlen($data['ruc']) > 13)) {
            $errors['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
        }

        if ($data['telefono_fijo'] !== '' && strlen($data['telefono_fijo']) !== 7) {
            $errors['telefono_fijo'] = 'El teléfono fijo debe tener 7 dígitos';
        }

        if ($data['telefono_movil'] !== '' && strlen($data['telefono_movil']) !== 10) {
            $errors['telefono_movil'] = 'El celular debe tener 10 dígitos';
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo debe tener un formato válido';
        }

        if ($data['direccion_calle'] !== '' && mb_strlen($data['direccion_calle']) > 255) {
            $errors['direccion_calle'] = 'La calle no puede exceder 255 caracteres';
        }

        if ($data['direccion_interseccion'] !== '' && mb_strlen($data['direccion_interseccion']) > 255) {
            $errors['direccion_interseccion'] = 'La intersección no puede exceder 255 caracteres';
        }

        $permitidos = ['cooperativa', 'mutualista', 'sujeto_no_financiero', 'caja_ahorros', 'casa_valores'];
        if (!in_array($data['tipo_entidad'], $permitidos, true)) {
            $data['tipo_entidad'] = 'cooperativa';
        }

        if ($data['tipo_entidad'] !== 'cooperativa') {
            $data['id_segmento'] = null;
        }

        return [
            'ok'     => empty($errors),
            'errors' => $errors,
            'data'   => $data,
        ];
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
        $dateTime = DateTimeImmutable::createFromFormat($format, $value);
        if ($dateTime === false) {
            return false;
        }

        return $dateTime->format($format) === $value;
    }

    public function dateTimeFuture(string $value, string $format = DateTimeInterface::ATOM): bool
    {
        if (!$this->dateTimeValid($value, $format)) {
            return false;
        }

        $dateTime = DateTimeImmutable::createFromFormat($format, $value);
        if ($dateTime === false) {
            return false;
        }

        return $dateTime > new DateTimeImmutable();
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
