<?php
declare(strict_types=1);

namespace App\Services\Comercial;

use App\Repositories\Comercial\AgendaRepository;
use App\Services\Shared\ValidationService;
use DateTimeImmutable;
use RuntimeException;

final class AgendaService
{
    public function __construct(
        private AgendaRepository $repository,
        private ValidationService $validator
    ) {
    }

    /**
     * @param array{texto?:string,desde?:string,hasta?:string,estado?:string} $filters
     */
    public function list(array $filters, int $page, int $perPage): array
    {
        $clean = [
            'texto'  => trim((string)($filters['texto'] ?? '')),
            'desde'  => trim((string)($filters['desde'] ?? '')),
            'hasta'  => trim((string)($filters['hasta'] ?? '')),
            'estado' => trim((string)($filters['estado'] ?? '')),
        ];

        $page    = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));

        return $this->repository->search($clean, $page, $perPage);
    }

    public function get(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        return $this->repository->findById($id);
    }

    public function create(array $input): int
    {
        $data = $this->validate($input);
        return $this->repository->create($data);
    }

    public function update(int $id, array $input): void
    {
        if ($id < 1) {
            throw new RuntimeException($this->encodeErrors(['id' => 'Identificador inválido']));
        }

        $data = $this->validate($input);
        $this->repository->update($id, $data);
    }

    public function changeStatus(int $id, string $estado): void
    {
        if ($id < 1) {
            throw new RuntimeException($this->encodeErrors(['id' => 'Identificador inválido']));
        }

        $estado = strtolower(trim($estado));
        if ($estado === '') {
            $estado = 'pendiente';
        }

        if (!$this->isEstadoValido($estado)) {
            throw new RuntimeException($this->encodeErrors(['estado' => 'Estado inválido']));
        }

        $this->repository->updateEstado($id, $estado);
    }

    public function delete(int $id): void
    {
        if ($id < 1) {
            throw new RuntimeException($this->encodeErrors(['id' => 'Identificador inválido']));
        }

        $this->repository->delete($id);
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function validate(array $input): array
    {
        $errors = [];

        $idCooperativa = isset($input['id_cooperativa']) ? (int)$input['id_cooperativa'] : 0;
        if ($idCooperativa < 1) {
            $errors['id_cooperativa'] = 'Selecciona una entidad válida';
        }

        $titulo = trim((string)($input['titulo'] ?? ''));
        if (!$this->validator->stringLength($titulo, 1, 160)) {
            $errors['titulo'] = 'El título debe tener entre 1 y 160 caracteres';
        }

        $descripcion = trim((string)($input['descripcion'] ?? ''));
        if ($descripcion === '') {
            $descripcion = null;
        }

        $fechaEntrada = trim((string)($input['fecha_evento'] ?? ''));
        $fechaNormalizada = null;
        if ($fechaEntrada === '') {
            $errors['fecha_evento'] = 'La fecha del evento es obligatoria';
        } else {
            $fechaNormalizada = $this->normalizarFecha($fechaEntrada);
            if ($fechaNormalizada === null) {
                $errors['fecha_evento'] = 'La fecha del evento es inválida';
            }
        }

        $telefono = trim((string)($input['telefono_contacto'] ?? ''));
        if ($telefono === '') {
            $telefono = null;
        } else {
            $soloDigitos = preg_replace('/\D+/', '', $telefono) ?? '';
            if ($soloDigitos === '') {
                $errors['telefono_contacto'] = 'El teléfono solo debe contener dígitos';
                $telefono = null;
            } elseif (!$this->validator->digits($soloDigitos, 7, 20)) {
                $errors['telefono_contacto'] = 'El teléfono debe tener entre 7 y 20 dígitos';
                $telefono = null;
            } else {
                $telefono = $soloDigitos;
            }
        }

        $email = trim((string)($input['email_contacto'] ?? ''));
        if ($email === '') {
            $email = null;
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email_contacto'] = 'El email es inválido';
        }

        $estado = strtolower(trim((string)($input['estado'] ?? 'pendiente')));
        if ($estado === '') {
            $estado = 'pendiente';
        }
        if (!$this->isEstadoValido($estado)) {
            $errors['estado'] = 'Estado inválido';
        }

        if ($errors) {
            throw new RuntimeException($this->encodeErrors($errors));
        }

        return [
            'id_cooperativa'    => $idCooperativa,
            'titulo'            => $titulo,
            'descripcion'       => $descripcion,
            'fecha_evento'      => $fechaNormalizada ?? $fechaEntrada,
            'telefono_contacto' => $telefono,
            'email_contacto'    => $email,
            'estado'            => $estado,
        ];
    }

    private function normalizarFecha(string $value): ?string
    {
        $formatos = [
            'Y-m-d\TH:i',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d H:i:s',
            'Y-m-d',
        ];

        foreach ($formatos as $formato) {
            if ($this->validator->dateTimeValid($value, $formato)) {
                $dt = DateTimeImmutable::createFromFormat($formato, $value);
                if ($dt instanceof DateTimeImmutable) {
                    return $dt->format('Y-m-d H:i:s');
                }
            }
        }

        return null;
    }

    private function isEstadoValido(string $estado): bool
    {
        return in_array($estado, ['pendiente', 'completado', 'cancelado'], true);
    }

    private function encodeErrors(array $errors): string
    {
        $json = json_encode($errors, JSON_UNESCAPED_UNICODE);
        return $json === false ? '{}' : $json;
    }
}
