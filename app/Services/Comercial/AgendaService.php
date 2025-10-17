<?php
declare(strict_types=1);

namespace App\Services\Comercial;

use App\Repositories\Comercial\AgendaRepository;

final class AgendaService
{
    public function __construct(private AgendaRepository $repository)
    {
    }

    /**
     * @return array<int,array{id:int,nombre:string}>
     */
    public function cooperativas(): array
    {
        return $this->repository->obtenerCooperativas();
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function listado(array $filters): array
    {
        $clean = [
            'q' => trim((string)($filters['q'] ?? '')),
            'coop' => $this->normalizarEntero($filters['coop'] ?? null),
            'estado' => $this->normalizarEstado($filters['estado'] ?? ''),
        ];

        return $this->repository->listar($clean);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function datosParaExportar(array $filters): array
    {
        $clean = [
            'q' => trim((string)($filters['q'] ?? '')),
            'coop' => $this->normalizarEntero($filters['coop'] ?? null),
            'estado' => $this->normalizarEstado($filters['estado'] ?? ''),
        ];

        return $this->repository->listarParaExportar($clean);
    }

    /**
     * @param array<string,mixed> $input
     * @return array{ok:bool,errors:array<string,string>,data:array<string,mixed>,id?:int}
     */
    public function crear(array $input, ?int $userId): array
    {
        [$payload, $old, $errors] = $this->validar($input);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'data' => $old];
        }

        $payload['creado_por'] = $userId;
        $id = $this->repository->crear($payload);

        return [
            'ok' => true,
            'errors' => [],
            'data' => $old,
            'id' => $id,
        ];
    }

    /**
     * @return array{ok:bool,errors:array<string,string>}
     */
    public function cambiarEstado(int $id, string $estado): array
    {
        if ($id < 1) {
            return ['ok' => false, 'errors' => ['id' => 'Identificador inválido']];
        }

        $normalizado = $this->normalizarEstado($estado);
        if ($normalizado === '') {
            return ['ok' => false, 'errors' => ['estado' => 'Estado inválido']];
        }

        $this->repository->cambiarEstado($id, $normalizado);
        return ['ok' => true, 'errors' => []];
    }

    public function eliminar(int $id): bool
    {
        if ($id < 1) {
            return false;
        }
        $this->repository->eliminar($id);
        return true;
    }

    public function obtener(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        return $this->repository->obtenerPorId($id);
    }

    /**
     * @param array<string,mixed> $input
     * @return array{0:array<string,mixed>,1:array<string,mixed>,2:array<string,string>}
     */
    private function validar(array $input): array
    {
        $errors = [];

        $idCooperativa = $this->normalizarEntero($input['id_cooperativa'] ?? null);
        $titulo = trim((string)($input['titulo'] ?? ''));
        $fecha = trim((string)($input['fecha_evento'] ?? ''));
        $nombre = trim((string)($input['oficial_nombre'] ?? ''));
        $telefonoEntrada = trim((string)($input['telefono_contacto'] ?? ''));
        $correo = trim((string)($input['oficial_correo'] ?? ''));
        $cargo = trim((string)($input['cargo'] ?? ''));
        $nota = trim((string)($input['nota'] ?? ''));

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $errors['titulo'] = 'Ingresa un título (máximo 150 caracteres)';
        }

        $fechaNormalizada = $this->validarFecha($fecha);
        if ($fechaNormalizada === null) {
            $errors['fecha_evento'] = 'Selecciona una fecha válida (AAAA-MM-DD)';
        }

        $telefonoLimpio = null;
        if ($telefonoEntrada !== '') {
            $soloDigitos = preg_replace('/\D+/', '', $telefonoEntrada) ?? '';
            $longitud = strlen($soloDigitos);
            if ($soloDigitos === '' || $longitud < 7 || $longitud > 10) {
                $errors['telefono_contacto'] = 'El teléfono debe tener entre 7 y 10 dígitos';
            } else {
                $telefonoLimpio = $soloDigitos;
            }
        }

        if ($correo !== '' && filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errors['oficial_correo'] = 'Ingresa un correo válido';
        }

        if ($idCooperativa !== null && $idCooperativa < 1) {
            $errors['id_cooperativa'] = 'Selecciona una entidad válida';
        }

        $payload = [
            'id_cooperativa' => $idCooperativa,
            'titulo' => $titulo,
            'fecha_evento' => $fechaNormalizada ?? $fecha,
            'contacto' => $nombre !== '' ? $nombre : null,
            'telefono_contacto' => $telefonoLimpio,
            'oficial_nombre' => $nombre !== '' ? $nombre : null,
            'oficial_correo' => $correo !== '' ? $correo : null,
            'cargo' => $cargo !== '' ? $cargo : null,
            'nota' => $nota !== '' ? $nota : null,
            'estado' => 'Pendiente',
        ];

        $old = [
            'id_cooperativa' => $input['id_cooperativa'] ?? '',
            'titulo' => $titulo,
            'fecha_evento' => $fecha,
            'oficial_nombre' => $nombre,
            'telefono_contacto' => $telefonoEntrada,
            'oficial_correo' => $correo,
            'cargo' => $cargo,
            'nota' => $nota,
        ];

        return [$payload, $old, $errors];
    }

    private function normalizarEntero(null|int|string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value) && !ctype_digit($value)) {
            return null;
        }
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }

    private function validarFecha(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($dt instanceof \DateTimeImmutable) {
            return $dt->format('Y-m-d');
        }
        return null;
    }

    private function normalizarEstado(string $estado): string
    {
        $map = [
            'pendiente' => 'Pendiente',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
        ];
        $key = strtolower(trim($estado));
        return $map[$key] ?? '';
    }
}
