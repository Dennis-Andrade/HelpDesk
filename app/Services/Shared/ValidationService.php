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
        $digits = static fn($value): string => preg_replace('/\D+/', '', (string)$value);
        $intOrNull = static function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
            if (!is_numeric($value)) {
                return null;
            }
            return (int)$value;
        };

        $telefono = trim((string)($in['telefono'] ?? $in['telefono_principal'] ?? ''));
        $segmentoRaw = trim((string)($in['segmento'] ?? $in['id_segmento'] ?? ''));
        $segmentoId = $segmentoRaw === '' ? null : (ctype_digit($segmentoRaw) ? (int)$segmentoRaw : null);

        $data = array(
            'nombre'       => trim((string)($in['nombre'] ?? '')),
            'ruc'          => $digits($in['ruc'] ?? $in['nit'] ?? ''),
            'telefono'     => $telefono,
            'email'        => trim((string)($in['email'] ?? '')),
            'provincia_id' => $intOrNull($in['provincia_id'] ?? null),
            'canton_id'    => $intOrNull($in['canton_id'] ?? null),
            'segmento'     => $segmentoRaw,
            'id_segmento'  => $segmentoId,
        );
        $data['nit'] = $data['ruc'];

        $errors = array();

        if ($data['nombre'] === '') {
            $errors['nombre'] = 'El nombre es obligatorio';
        }

        if ($data['ruc'] !== '' && (strlen($data['ruc']) < 10 || strlen($data['ruc']) > 13)) {
            $errors['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
        }

        if ($data['telefono'] !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $data['telefono'])) {
            $errors['telefono'] = 'El teléfono debe tener entre 6 y 20 caracteres numéricos';
        }

        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        if ($segmentoRaw !== '' && $segmentoId === null) {
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
