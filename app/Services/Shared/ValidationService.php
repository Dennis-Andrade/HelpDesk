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
            if ($v === '' || $v === null) return null;
            if (is_numeric($v)) return (int)$v;
            return null;
        };

        // Normalización
        $emailInput = $in['email'] ?? '';
        $emailTrim  = trim((string)$emailInput);

        $data = [
            'nombre'          => trim((string)($in['nombre'] ?? '')),
            'ruc'             => $digits($in['nit'] ?? $in['ruc'] ?? ''), // admite 'nit' o 'ruc'
            'telefono_fijo'   => $digits($in['telefono_fijo'] ?? $in['tfijo'] ?? ''),
            'telefono_movil'  => $digits($in['telefono_movil'] ?? $in['tmov'] ?? ''),
            'email'           => $emailTrim,
            'provincia_id'    => $intOrNull($in['provincia_id'] ?? null),
            'canton_id'       => $intOrNull($in['canton_id'] ?? null),
            'tipo_entidad'    => trim((string)($in['tipo_entidad'] ?? 'cooperativa')),
            'id_segmento'     => $intOrNull($in['id_segmento'] ?? null),
            'notas'           => trim((string)($in['notas'] ?? '')),
        ];
        // Alias para compatibilidad con repositorios que esperan 'nit'.
        $data['nit'] = $data['ruc'];

        // servicios[] (opcional)
        $serv = $in['servicios'] ?? [];
        if (!is_array($serv)) $serv = [];
        $data['servicios'] = array_values(array_unique(array_map(
            static fn($x) => (int)$x,
            array_filter($serv, static fn($x) => is_numeric($x))
        )));

        // Validación
        $e = [];

        if ($data['nombre'] === '') {
            $e['nombre'] = 'El nombre es obligatorio';
        }

        // ruc: si viene, 10-13 dígitos
        if ($data['ruc'] !== '' && (strlen($data['ruc']) < 10 || strlen($data['ruc']) > 13)) {
            $e['ruc'] = 'La cédula/RUC debe tener entre 10 y 13 dígitos';
        }

        // fijo: si viene, 7 dígitos
        if ($data['telefono_fijo'] !== '' && strlen($data['telefono_fijo']) !== 7) {
            $e['telefono_fijo'] = 'El teléfono fijo debe tener 7 dígitos';
        }

        // móvil: si viene, 10 dígitos
        if ($data['telefono_movil'] !== '' && strlen($data['telefono_movil']) !== 10) {
            $e['telefono_movil'] = 'El celular debe tener 10 dígitos';
        }

        // tipo_entidad: valores permitidos
        $permitidos = ['cooperativa','mutualista','sujeto_no_financiero','caja_ahorros','casa_valores'];
        if ($data['tipo_entidad'] === '' || !in_array($data['tipo_entidad'], $permitidos, true)) {
            // por defecto dejamos "cooperativa"
            $data['tipo_entidad'] = 'cooperativa';
        }
      
        if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Debe contener @ para ser un correo válido';
        }

        if ($data['tipo_entidad'] !== 'cooperativa') {
            $data['id_segmento'] = null;
        }

        if ($data['email'] === '') {
            $e['email'] = 'El correo electrónico es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Debe contener @ para ser un correo válido';
        }

        if ($data['tipo_entidad'] !== 'cooperativa') {
            $data['id_segmento'] = null;
        }

        if ($data['email'] === '') {
            $e['email'] = 'El correo electrónico es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Debe contener @ para ser un correo válido';
        }

        if ($data['tipo_entidad'] !== 'cooperativa') {
            $data['id_segmento'] = null;
        }

        if ($data['email'] === '') {
            $e['email'] = 'El correo electrónico es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Debe contener @ para ser un correo válido';
        }

        if ($data['tipo_entidad'] !== 'cooperativa') {
            $data['id_segmento'] = null;
        }

        return ['ok' => empty($e), 'errors' => $e, 'data' => $data];
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
