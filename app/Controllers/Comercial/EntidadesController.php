<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\EntidadRepository;
use App\Services\Comercial\BuscarEntidadesService;
use App\Services\Shared\Breadcrumbs;
use App\Services\Shared\UbicacionesService;
use App\Services\Shared\ValidationService;
use App\Support\Logger;
use PDOException;
use function csrf_token;
use function csrf_verify;
use function redirect;
use function view;

final class EntidadesController
{
    /** @var BuscarEntidadesService */
    private $buscar;
    /** @var EntidadRepository */
    private $repo;
    /** @var ValidationService */
    private $validator;
    /** @var UbicacionesService */
    private $ubicaciones;

    public function __construct(
        ?BuscarEntidadesService $buscar = null,
        ?EntidadRepository $repo = null,
        ?ValidationService $validator = null,
        ?UbicacionesService $ubicaciones = null
    ) {
        $this->repo        = $repo ?? new EntidadRepository();
        $this->validator   = $validator ?? new ValidationService();
        $this->ubicaciones = $ubicaciones ?? new UbicacionesService();
        $this->buscar      = $buscar ?? new BuscarEntidadesService($this->repo, $this->validator);
    }

    public function index(): void
    {
        $filters = is_array($_GET) ? $_GET : array();
        $q       = isset($filters['q']) ? trim((string)$filters['q']) : null;
        $page    = isset($filters['page']) ? (int)$filters['page'] : 1;

        $result = $this->buscar->buscar($q, $page, 12);

        $toast = $this->pullFlash('ok');
        $error = $this->pullFlash('error');

        view('comercial/entidades/index_cards', array(
            'layout'       => 'layout',
            'title'        => 'Entidades financieras',
            'items'        => $result['items'],
            'total'        => $result['total'],
            'page'         => $result['page'],
            'perPage'      => $result['perPage'],
            'q'            => $q ?? '',
            'csrf'         => csrf_token(),
            'toastMessage' => $toast,
            'errorMessage' => $error,
        ));
    }

    public function showJson($id): void
    {
        $id = (int)$id;
        if ($id < 1) {
            $this->respondEntidadJson(array('error' => 'ID inválido'), 400);
            return;
        }

        try {
            $row = $this->repo->findCardById($id);
        } catch (\Throwable $e) {
            $this->respondEntidadJson(array('error' => 'Error interno'), 500);
            return;
        }

        if ($row === null) {
            $this->respondEntidadJson(array('error' => 'Entidad no encontrada'), 404);
            return;
        }

        $payload = array(
            'data' => array(
                'id'        => (int)$row['id'],
                'nombre'    => (string)$row['nombre'],
                'ruc'       => $row['ruc'] ?? null,
                'telefono'  => $row['telefono'] ?? null,
                'email'     => $row['email'] ?? null,
                'provincia' => $row['provincia'] ?? null,
                'canton'    => $row['canton'] ?? null,
                'segmento'  => $row['segmento'] ?? null,
                'servicios' => $row['servicios_text'] ?? null,
            ),
        );

        $this->respondEntidadJson($payload, 200);
    }

    public function createForm(): void
    {
        $crumbs = Breadcrumbs::make(array(
            array('href' => '/comercial', 'label' => 'Comercial'),
            array('href' => '/comercial/entidades', 'label' => 'Entidades'),
            array('label' => 'Crear'),
        ));

        $errors = $this->pullFlashData('create_errors');
        $old    = $this->pullFlashData('create_old');

        view('comercial/entidades/create', array(
            'title'      => 'Nueva Entidad',
            'crumbs'     => $crumbs,
            'csrf'       => csrf_token(),
            'errors'     => $errors,
            'old'        => $old,
            'provincias' => $this->ubicaciones->provincias(),
            'cantones'   => array(),
            'action'     => '/comercial/entidades',
        ));
    }

    public function editForm(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id < 1) {
            redirect('/comercial/entidades');
            return;
        }

        $row = $this->repo->findById($id);
        if ($row === null) {
            redirect('/comercial/entidades');
            return;
        }

        $crumbs = Breadcrumbs::make(array(
            array('href' => '/comercial', 'label' => 'Comercial'),
            array('href' => '/comercial/entidades', 'label' => 'Entidades'),
            array('label' => 'Editar'),
        ));

        $errors = $this->pullFlashData('edit_errors');
        $old    = $this->pullFlashData('edit_old');

        if (!empty($old)) {
            $row = array_merge($row, $old);
        }

        $provinciaId = isset($row['provincia_id']) ? (int)$row['provincia_id'] : 0;

        view('comercial/entidades/edit', array(
            'title'      => 'Editar Entidad',
            'crumbs'     => $crumbs,
            'item'       => $row,
            'csrf'       => csrf_token(),
            'errors'     => $errors,
            'provincias' => $this->ubicaciones->provincias(),
            'cantones'   => $this->ubicaciones->cantones($provinciaId),
            'action'     => '/comercial/entidades/' . $id,
        ));
    }

    public function store(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $validation = $this->validator->validarEntidad($_POST);
        if (!$validation['ok']) {
            $this->flash('error', 'Revisa los campos del formulario');
            $this->flashData('create_errors', $validation['errors']);
            $this->flashData('create_old', $this->oldInput($validation['data']));
            redirect('/comercial/entidades/crear');
            return;
        }

        try {
            $this->repo->create($validation['data']);
        } catch (PDOException $e) {
            error_log('PDOException: ' . $e->getMessage() . ' | ' . json_encode($e->errorInfo ?? array()));
            $this->flash('error', 'No se pudo guardar la entidad');
            $this->flashData('create_errors', array('general' => 'No se pudo guardar la entidad'));
            $this->flashData('create_old', $this->oldInput($validation['data']));
            redirect('/comercial/entidades/crear');
            return;
        } catch (\Throwable $e) {
            Logger::error($e, 'EntidadesController::store');
            $this->flash('error', 'No se pudo guardar la entidad');
            $this->flashData('create_old', $this->oldInput($validation['data']));
            redirect('/comercial/entidades/crear');
            return;
        }

        $this->flash('ok', 'Entidad creada correctamente');
        redirect('/comercial/entidades');
    }

    public function update($id): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $id = (int)$id;
        if ($id < 1) {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        }
        if ($id < 1) {
            redirect('/comercial/entidades');
            return;
        }

        $validation = $this->validator->validarEntidad($_POST);
        if (!$validation['ok']) {
            $this->flash('error', 'Revisa los campos del formulario');
            $this->flashData('edit_errors', $validation['errors']);
            $this->flashData('edit_old', $this->oldInput($validation['data']));
            redirect('/comercial/entidades/editar?id=' . $id);
            return;
        }

        try {
            $this->repo->update($id, $validation['data']);
        } catch (PDOException $e) {
            error_log('PDOException: ' . $e->getMessage() . ' | ' . json_encode($e->errorInfo ?? array()));
            $this->flash('error', 'No se pudieron guardar los cambios');
            $this->flashData('edit_errors', array('general' => 'No se pudieron guardar los cambios'));
            $this->flashData('edit_old', $this->oldInput($validation['data']));
            redirect('/comercial/entidades/editar?id=' . $id);
            return;
        } catch (\Throwable $e) {
            Logger::error($e, 'EntidadesController::update');
            $this->flash('error', 'No se pudieron guardar los cambios');
            $this->flashData('edit_old', $this->oldInput($validation['data']));
            redirect('/comercial/entidades/editar?id=' . $id);
            return;
        }

        $this->flash('ok', 'Cambios guardados');
        redirect('/comercial/entidades');
    }

    public function delete(): void
    {
        if (!csrf_verify($_POST['_csrf'] ?? '')) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id < 1) {
            redirect('/comercial/entidades');
            return;
        }

        try {
            $this->repo->delete($id);
            $this->flash('ok', 'Entidad eliminada');
        } catch (PDOException $e) {
            error_log('PDOException: ' . $e->getMessage() . ' | ' . json_encode($e->errorInfo ?? array()));
            $this->flash('error', 'No se pudo eliminar la entidad');
        } catch (\Throwable $e) {
            Logger::error($e, 'EntidadesController::delete');
            $this->flash('error', 'No se pudo eliminar la entidad');
        }

        redirect('/comercial/entidades');
    }

    private function respondEntidadJson(array $payload, $status = 200): void
    {
        http_response_code((int)$status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @return array<string,mixed> */
    private function oldInput(array $input): array
    {
        $old = array();
        foreach ($input as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $old[$key] = $value === null ? null : (is_string($value) ? trim($value) : $value);
                continue;
            }

            if ($key === 'servicios' && is_array($value)) {
                $filtered = array();
                foreach ($value as $item) {
                    if (is_int($item)) {
                        if ($item > 0) {
                            $filtered[] = $item;
                        }
                        continue;
                    }
                    if (is_string($item) && $item !== '' && is_numeric($item)) {
                        $intValue = (int)$item;
                        if ($intValue > 0) {
                            $filtered[] = $intValue;
                        }
                    }
                }
                $old[$key] = $filtered;
            }
        }
        return $old;
    }

    private function flash(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['entidades_flash_' . $type] = $message;
    }

    private function pullFlash(string $type): ?string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $key = 'entidades_flash_' . $type;
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
        $_SESSION['entidades_flash_data_' . $type] = $value;
    }

    /** @return array<string,mixed> */
    private function pullFlashData(string $type): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $key = 'entidades_flash_data_' . $type;
        if (!isset($_SESSION[$key])) {
            return array();
        }
        $value = $_SESSION[$key];
        unset($_SESSION[$key]);
        return is_array($value) ? $value : array();
    }
}
