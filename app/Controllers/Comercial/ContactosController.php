<?php
namespace App\Controllers\Comercial;

use App\Repositories\Comercial\ContactoRepository;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\Pagination;
use function \view;
use function \redirect;

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
     * Maneja la creación de un contacto a partir de los datos del POST.
     */
    public function create()
    {
        $data = [
            'id_cooperativa'    => (int)($_POST['id_entidad'] ?? 0),
            'nombre'            => trim((string)($_POST['nombre'] ?? '')),
            'titulo'            => trim((string)($_POST['titulo'] ?? '')),
            'cargo'             => trim((string)($_POST['cargo'] ?? '')),
            'telefono_contacto' => trim((string)($_POST['telefono'] ?? '')),
            'email_contacto'    => trim((string)($_POST['correo'] ?? '')),
            'nota'              => trim((string)($_POST['nota'] ?? '')),
        ];
        $this->repo->create($data);
        redirect('/comercial/contactos');
    }

    /**
     * Muestra el formulario para editar un contacto existente.
     */
    public function editForm()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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

        $data = [
            'id_cooperativa'    => (int)($_POST['id_entidad'] ?? 0),
            'nombre'            => trim((string)($_POST['nombre'] ?? '')),
            'titulo'            => trim((string)($_POST['titulo'] ?? '')),
            'cargo'             => trim((string)($_POST['cargo'] ?? '')),
            'telefono_contacto' => trim((string)($_POST['telefono'] ?? '')),
            'email_contacto'    => trim((string)($_POST['correo'] ?? '')),
            'nota'              => trim((string)($_POST['nota'] ?? '')),
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
}
