<?php
declare(strict_types=1);

namespace App\Controllers\Comercial;

use App\Repositories\Comercial\ContactoRepository;
use App\Repositories\Comercial\EntidadRepository;
use App\Services\Shared\Pagination;
use function view;
use function redirect;

/**
 * Controlador para la agenda de contactos.
 *
 * ...
 * (aquí va todo el contenido del archivo)
 */
class ContactosController
{
    private ContactoRepository $repo;
    private EntidadRepository $entidades;

    public function __construct(
        ?ContactoRepository $repo = null,
        ?EntidadRepository $entidades = null
    ) {
        $this->repo      = $repo ?? new ContactoRepository();
        $this->entidades = $entidades ?? new EntidadRepository();
    }

    // ... resto del controlador (métodos index, create, delete, etc.)
}
