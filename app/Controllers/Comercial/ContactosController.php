<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\ContactoRepository;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\Pagination;
use function \view;
use function \redirect;
use const \FILTER_VALIDATE_EMAIL;

/**
 * Controlador para la agenda de contactos.
 *
 * Este controlador orquesta la visualización y creación de contactos
 * asociados a las entidades financieras. Utiliza el patrón MVC presente
 * en la aplicación para separar responsabilidades y delegar el acceso
 * a datos al repositorio correspondiente.
 */
final class ContactosController
{
    /** @var ContactoRepository */
    private $repo;
    /** @var EntidadRepository */
    private $entidades;

    public function __construct(
        ?ContactoRepository $repo = null,
        ?EntidadRepository $entidades = null
    ) {
        $this->repo      = $repo ?? new ContactoRepository();
        $this->entidades = $entidades ?? new EntidadRepository();
    }

    /**
     * Muestra la lista de contactos junto con el formulario de alta.
     *
     * Acepta parámetros de paginación y filtro mediante query string y
     * pasa la información necesaria a la vista.
     */
    public function index()
    {
        $filters = is_array($_GET) ? $_GET : [];
        $q       = trim((string)($filters['q'] ?? ''));
        $pager   = Pagination::fromRequest($filters, 1, 10, 0);
        $result  = $this->repo->search($q, $pager->page, $pager->perPage);

        return view('comercial/contactos/index', [
            'layout'    => 'layout',
            'title'     => 'Agenda de contactos',
            'items'     => $result['items'],
            'total'     => $result['total'],
            'page'      => $result['page'],
            'perPage'   => $result['perPage'],
            'q'         => $q,
            'filters'   => $filters,
            'entidades' => $this->listadoEntidades(),
        ]);
    }

    /**
     * Devuelve sugerencias de búsqueda para el cuadro de texto principal.
     */
    public function suggest(): void
    {
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if (mb_strlen($q) < 3) {
            $this->respondJson(['items' => []]);
        }

        try {
            $rows = $this->repo->suggest($q, 12);
        } catch (\Throwable $e) {
            $this->respondJson(['items' => [], 'error' => 'No se pudo obtener sugerencias'], 500);
        }

        $contactItems = [];
        $entityItems  = [];

        foreach ($rows as $row) {
            $nombre  = trim((string)($row['nombre'] ?? ''));
            $entidad = trim((string)($row['entidad_nombre'] ?? ''));

            if ($entidad !== '' && !isset($entityItems[$entidad])) {
                $entityItems[$entidad] = [
                    'type' => 'entity',
                    'term' => $entidad,
                    'label'=> 'Entidad · ' . $entidad,
                ];
            }

            if ($nombre !== '') {
                $contactItems[] = [
                    'type'    => 'contact',
                    'term'    => $nombre,
                    'label'   => $nombre . ($entidad !== '' ? ' · ' . $entidad : ''),
                    'cargo'   => (string)($row['cargo'] ?? ''),
                    'entidad' => $entidad,
                ];
            }
        }

        $entities   = array_slice(array_values($entityItems), 0, 5);
        $contacts   = array_slice($contactItems, 0, 7);
        $items      = array_merge($entities, $contacts);

        $this->respondJson(['items' => $items]);
    }

    /**
     * Maneja la creación de un contacto a partir de los datos del POST.
     */
    public function create()
    {
        $telefono = preg_replace('/\D+/', '', (string)($_POST['telefono'] ?? '')) ?? '';
        if ($telefono !== '' && strlen($telefono) !== 10) {
            redirect('/comercial/contactos');
            return;
        }

        $correo = trim((string)($_POST['correo'] ?? ''));
        if ($correo !== '' && !\filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            redirect('/comercial/contactos');
            return;
        }

        $idEntidad = (int)($_POST['id_entidad'] ?? 0);
        $nombre    = trim((string)($_POST['nombre'] ?? ''));

        if ($idEntidad < 1 || $nombre === '') {
            redirect('/comercial/contactos');
            return;
        }

        $data = [
            'id_cooperativa'    => $idEntidad,
            'nombre'            => $nombre,
            'titulo'            => trim((string)($_POST['titulo'] ?? '')),
            'cargo'             => trim((string)($_POST['cargo'] ?? '')),
            'telefono_contacto' => $telefono,
            'email_contacto'    => $correo,
            'nota'              => trim((string)($_POST['nota'] ?? '')),
            'fecha_evento'      => trim((string)($_POST['fecha_evento'] ?? '')),
        ];
        $this->repo->create($data);
        redirect('/comercial/contactos');
    }

    /**
     * Muestra el formulario para editar un contacto existente.
     */
    public function editForm($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/comercial/contactos');
            return;
        }

        $contacto = $this->repo->find($id);
        if ($contacto === null) {
            redirect('/comercial/contactos');
            return;
        }

        return view('comercial/contactos/edit', [
            'layout'    => 'layout',
            'title'     => 'Editar contacto',
            'contacto'  => $contacto,
            'entidades' => $this->listadoEntidades(),
        ]);
    }

    /**
     * Actualiza un contacto.
     *
     * @param int|string $id
     */
    public function update($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            redirect('/comercial/contactos');
            return;
        }

        $telefono = preg_replace('/\D+/', '', (string)($_POST['telefono'] ?? '')) ?? '';
        if ($telefono !== '' && strlen($telefono) !== 10) {
            redirect('/comercial/contactos');
            return;
        }

        $postedId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($postedId > 0 && $postedId !== $id) {
            redirect('/comercial/contactos');
            return;
        }

        $correo = trim((string)($_POST['correo'] ?? ''));
        if ($correo !== '' && !\filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            redirect('/comercial/contactos');
            return;
        }

        $idEntidad = (int)($_POST['id_entidad'] ?? 0);
        $nombre    = trim((string)($_POST['nombre'] ?? ''));

        if ($idEntidad < 1 || $nombre === '') {
            redirect('/comercial/contactos');
            return;
        }

        $data = [
            'id_cooperativa'    => $idEntidad,
            'nombre'            => $nombre,
            'titulo'            => trim((string)($_POST['titulo'] ?? '')),
            'cargo'             => trim((string)($_POST['cargo'] ?? '')),
            'telefono_contacto' => $telefono,
            'email_contacto'    => $correo,
            'nota'              => trim((string)($_POST['nota'] ?? '')),
            'fecha_evento'      => trim((string)($_POST['fecha_evento'] ?? '')),
        ];
        $this->repo->update($id, $data);
        redirect('/comercial/contactos');
    }

    /**
     * Elimina un contacto identificado por su id.
     *
     * @param int|string $id Identificador del contacto a eliminar.
     */
    public function delete($id)
    {
        $id = (int)$id;
        if ($id < 1) {
            $id = (int)($_POST['id'] ?? 0);
        }
        if ($id > 0) {
            $this->repo->delete($id);
        }
        redirect('/comercial/contactos');
    }

    /**
     * Devuelve una lista de entidades para el selector desplegable.
     *
     * @return array<int,array{id:int,nombre:string}>
     */
    private function listadoEntidades(): array
    {
        $resultado = $this->entidades->search('', 1, 200);
        $items     = $resultado['items'] ?? [];
        $list      = [];
        foreach ($items as $item) {
            if (!isset($item['id'], $item['nombre'])) {
                continue;
            }
            $list[] = [
                'id'     => (int)$item['id'],
                'nombre' => (string)$item['nombre'],
            ];
        }
        return $list;
    }

    /**
     * Envía una respuesta JSON y termina la ejecución.
     *
     * @param array<string,mixed> $payload
     */
    private function respondJson(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
