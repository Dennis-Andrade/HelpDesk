<?php
declare(strict_types=1);

namespace App\Controllers\Comercial;

use App\Repositories\Comercial\AgendaRepository;
use App\Repositories\Comercial\EntidadQueryRepository;
use App\Services\Comercial\AgendaService;
use App\Services\Shared\ValidationService;
use Config\Cnxn;
use RuntimeException;
use function csrf_token;
use function csrf_verify;
use function redirect;
use function view;

final class AgendaController
{
    private const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'completado' => 'Completado',
        'cancelado'  => 'Cancelado',
    ];

    private AgendaService $service;
    private EntidadQueryRepository $entidades;

    public function __construct(?AgendaService $service = null, ?EntidadQueryRepository $entidades = null)
    {
        $pdo = Cnxn::pdo();
        $this->service    = $service ?? new AgendaService(new AgendaRepository(), new ValidationService());
        $this->entidades  = $entidades ?? new EntidadQueryRepository($pdo);
    }

    public function index(): void
    {
        $filters = [
            'texto'  => trim((string)($_GET['texto'] ?? '')),
            'desde'  => trim((string)($_GET['desde'] ?? '')),
            'hasta'  => trim((string)($_GET['hasta'] ?? '')),
            'estado' => trim((string)($_GET['estado'] ?? '')),
        ];
        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;

        $result = $this->service->list($filters, $page, $perPage);

        $editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
        $editTarget = null;
        if ($editId > 0) {
            $editTarget = $this->service->get($editId);
        }

        $editErrors = $this->pullFlashData('edit_errors');
        $editOld    = $this->pullFlashData('edit_old');
        if (!empty($editOld)) {
            $editTarget = array_merge((array)$editTarget, $editOld);
        }

        view('comercial/agenda/index', [
            'title'      => 'Agenda Comercial',
            'csrf'       => csrf_token(),
            'filters'    => $filters,
            'items'      => $result['data'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'perPage'    => $result['per_page'],
            'estados'    => self::ESTADOS,
            'entidades'  => $this->listadoEntidades(),
            'flashError' => $this->pullFlash('error'),
            'flashOk'    => $this->pullFlash('ok'),
            'editTarget' => $editTarget,
            'editErrors' => $editErrors,
            'editOld'    => $editOld,
        ]);
    }

    public function showJson(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($id < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'ID inválido'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $evento = $this->service->get($id);
        if ($evento === null) {
            http_response_code(404);
            echo json_encode(['error' => 'Evento no encontrado'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode($evento, JSON_UNESCAPED_UNICODE);
    }

    public function create(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        try {
            $this->service->create($_POST);
            $this->flash('ok', 'Evento registrado correctamente');
            redirect('/comercial/agenda');
        } catch (RuntimeException $e) {
            $errors = $this->decodeErrors($e);
            view('comercial/agenda/create', [
                'title'     => 'Nuevo evento',
                'csrf'      => csrf_token(),
                'errors'    => $errors,
                'old'       => $this->oldInput($_POST),
                'estados'   => self::ESTADOS,
                'entidades' => $this->listadoEntidades(),
            ]);
        }
    }

    public function edit(int $id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        try {
            $this->service->update($id, $_POST);
            $this->flash('ok', 'Evento actualizado');
            redirect('/comercial/agenda');
        } catch (RuntimeException $e) {
            $errors = $this->decodeErrors($e);
            $this->flashData('edit_errors', $errors);
            $this->flashData('edit_old', $this->oldInput($_POST));
            $this->flash('error', $this->errorsToText($errors));
            redirect('/comercial/agenda?edit=' . $id);
        }
    }

    public function changeStatus(int $id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        try {
            $estado = (string)($_POST['estado'] ?? '');
            $this->service->changeStatus($id, $estado);
            $this->flash('ok', 'Estado actualizado');
        } catch (RuntimeException $e) {
            $this->flash('error', $this->errorsToText($this->decodeErrors($e)));
        }

        redirect('/comercial/agenda');
    }

    public function delete(int $id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        try {
            $this->service->delete($id);
            $this->flash('ok', 'Evento eliminado');
        } catch (RuntimeException $e) {
            $this->flash('error', $this->errorsToText($this->decodeErrors($e)));
        }

        redirect('/comercial/agenda');
    }

    /** @return array<string,string> */
    private function decodeErrors(RuntimeException $e): array
    {
        $decoded = json_decode($e->getMessage(), true);
        if (!is_array($decoded)) {
            return ['general' => 'No se pudo procesar la solicitud'];
        }

        /** @var array<string,string> $decoded */
        return $decoded;
    }

    /** @return array<string,mixed> */
    private function oldInput(array $input): array
    {
        $keep = ['id_cooperativa','titulo','descripcion','fecha_evento','telefono_contacto','email_contacto','estado'];
        $old  = [];
        foreach ($keep as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if (is_string($value)) {
                $old[$key] = trim($value);
            } else {
                $old[$key] = $value;
            }
        }
        return $old;
    }

    /** @return array<int, array{id:int,nombre:string}> */
    private function listadoEntidades(): array
    {
        $resultado = $this->entidades->search('', 1, 200);
        $items = $resultado['items'] ?? [];
        $list = [];
        foreach ($items as $item) {
            if (!isset($item['id'], $item['nombre'])) {
                continue;
            }
            $list[] = ['id' => (int)$item['id'], 'nombre' => (string)$item['nombre']];
        }
        return $list;
    }

    private function errorsToText(array $errors): string
    {
        $parts = [];
        foreach ($errors as $field => $message) {
            if (!is_string($message)) {
                continue;
            }
            $parts[] = ucfirst(str_replace('_', ' ', $field)) . ': ' . $message;
        }
        return $parts ? implode('. ', $parts) : 'No se pudo completar la operación';
    }

    private function flash(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['agenda_flash_' . $type] = $message;
    }

    private function pullFlash(string $type): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $key = 'agenda_flash_' . $type;
        if (!isset($_SESSION[$key])) {
            return null;
        }
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);
        return is_string($value) ? $value : null;
    }

    private function flashData(string $type, array $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['agenda_flash_data_' . $type] = $value;
    }

    /** @return array<string,mixed>|null */
    private function pullFlashData(string $type): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $key = 'agenda_flash_data_' . $type;
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            return null;
        }
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $value;
    }
}
