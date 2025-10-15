<?php
use App\Services\Shared\Pagination;

/** @var array $items Lista de contactos */
/** @var int   $total  Total de contactos */
/** @var int   $page   Página actual */
/** @var int   $perPage Elementos por página */
/** @var string $q     Búsqueda actual */
/** @var array $filters Filtros activos */
/** @var array<int,array{id:int,nombre:string}> $entidades Listado de entidades */

if (!function_exists('h')) {
    /**
     * Escapa valores para evitar XSS en las vistas.
     *
     * @param mixed $value
     * @return string
     */
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

// Normaliza parámetros para la paginación.
$pagination = Pagination::fromRequest([
    'page'    => (int)($page ?? 1),
    'perPage' => (int)($perPage ?? 10),
], 1, (int)($perPage ?? 10), (int)($total ?? 0));

$page    = $pagination->page;
$perPage = $pagination->perPage;
$pages   = $pagination->pages();
$prev    = max(1, $page - 1);
$next    = min($pages, $page + 1);

/**
 * Construye una URL con los filtros y la página indicada.
 *
 * @param int   $pageNumber
 * @param array $filters
 * @param int   $perPage
 * @return string
 */
function buildPageUrlContactos(int $pageNumber, array $filters, int $perPage): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return '/comercial/contactos' . ($queryString !== '' ? '?' . $queryString : '');
}

$today = date('Y-m-d');
?>
<section class="ent-list" aria-labelledby="contactos-title">
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="contactos-title" class="ent-title">Agenda de contactos</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)(int)$total) ?> contactos · Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?>
      </p>
    </div>
  </header>

  <section class="ent-container ent-contactos-list" aria-label="Contactos registrados">
    <form class="ent-search ent-search--stack ent-search--with-modal" action="/comercial/contactos" method="get" role="search">
      <div class="ent-search__field">
        <label for="contactos-search-input">Buscar por nombre o entidad</label>
        <input id="contactos-search-input" type="text" name="q" value="<?= h($q ?? '') ?>" aria-describedby="contactos-search-help" placeholder="Nombre o entidad" autocomplete="off" autocapitalize="none" spellcheck="false">
        <div id="contactos-search-suggestions" class="ent-search__suggestions" data-min-chars="3" role="listbox" aria-label="Sugerencias de búsqueda" hidden></div>
      </div>
      <?php foreach ($filters as $filterKey => $filterValue): ?>
        <?php if ($filterKey === 'q') { continue; } ?>
        <?php if (is_array($filterValue)): ?>
          <?php foreach ($filterValue as $fv): ?>
            <input type="hidden" name="<?= h((string)$filterKey) ?>[]" value="<?= h((string)$fv) ?>">
          <?php endforeach; ?>
        <?php else: ?>
          <input type="hidden" name="<?= h((string)$filterKey) ?>" value="<?= h((string)$filterValue) ?>">
        <?php endif; ?>
      <?php endforeach; ?>
      <span id="contactos-search-help" class="ent-search__help">Escribe al menos 3 caracteres para ver sugerencias</span>
      <div class="ent-search__actions">
        <button class="btn btn-outline" type="submit">Buscar</button>
        <button class="btn btn-primary ent-search__new" type="button" data-modal-open="contacto-crear-modal">
          <span class="material-symbols-outlined" aria-hidden="true">add</span>
          <span>Nuevo contacto</span>
        </button>
      </div>
    </form>

    <?php if (empty($items)): ?>
      <div class="card" role="status" aria-live="polite">No se encontraron contactos.</div>
    <?php else: ?>
      <?php $rowOffset = ($page - 1) * $perPage; ?>
      <div class="contact-list" role="table" aria-label="Listado de contactos">
        <div class="contact-list__header" role="row">
          <span class="contact-list__cell contact-list__cell--header contact-list__cell--num" role="columnheader">#</span>
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Nombre</span>
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Entidad</span>
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Fecha evento</span>
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Cargo</span>
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Celular</span>
          <span class="contact-list__cell contact-list__cell--header contact-list__cell--actions" role="columnheader">Acciones</span>
        </div>
        <?php foreach ($items as $index => $row): ?>
          <?php
            $contactId   = (int)($row['id'] ?? 0);
            $contactName = $row['nombre'] ?? 'Contacto';
            $entityName  = $row['entidad_nombre'] ?? '';
            $fechaEvento = $row['fecha_evento'] ?? '';
            $cargo       = $row['cargo'] ?? '';
            $telefono    = $row['telefono'] ?? '';
            $titulo      = $row['titulo'] ?? '';
            $correo      = $row['correo'] ?? '';
            $nota        = $row['nota'] ?? '';
            $rowNumber   = $rowOffset + $index + 1;
            $detailsId   = 'contact-details-' . ($contactId > 0 ? $contactId : ('row-' . $rowNumber));
          ?>
          <div class="contact-list__row" role="row">
            <span class="contact-list__cell contact-list__cell--num" data-label="Detalle" role="cell">
              <button type="button" class="contact-list__toggle" data-contact-toggle
                      aria-expanded="false" aria-controls="<?= h($detailsId) ?>">
                <?= h((string)$rowNumber) ?>
              </button>
            </span>
            <span class="contact-list__cell" data-label="Nombre" role="cell"><?= h($contactName) ?></span>
            <span class="contact-list__cell" data-label="Entidad" role="cell"><?= $entityName !== '' ? h($entityName) : '—' ?></span>
            <span class="contact-list__cell" data-label="Fecha evento" role="cell"><?= $fechaEvento !== '' ? h($fechaEvento) : '—' ?></span>
            <span class="contact-list__cell" data-label="Cargo" role="cell"><?= $cargo !== '' ? h($cargo) : '—' ?></span>
            <span class="contact-list__cell" data-label="Celular" role="cell"><?= $telefono !== '' ? h($telefono) : '—' ?></span>
            <span class="contact-list__cell contact-list__cell--actions" data-label="Acciones" role="cell">
              <button
                class="btn btn-primary"
                type="button"
                data-contact-edit
                data-modal-open="contacto-editar-modal"
                data-contact-id="<?= h((string)$contactId) ?>"
                data-contact-entidad="<?= isset($row['id_entidad']) ? h((string)$row['id_entidad']) : '' ?>"
                data-contact-nombre="<?= h($contactName) ?>"
                data-contact-titulo="<?= h($titulo) ?>"
                data-contact-cargo="<?= h($cargo) ?>"
                data-contact-telefono="<?= h($telefono) ?>"
                data-contact-correo="<?= h($correo) ?>"
                data-contact-nota="<?= h($nota) ?>"
                data-contact-fecha="<?= h($fechaEvento) ?>"
              >
                Editar
              </button>
              <form method="post" action="/comercial/contactos/<?= h((string)$contactId) ?>/eliminar" class="contact-list__delete" onsubmit="return confirm('¿Deseas eliminar este contacto?');">
                <button type="submit" class="btn btn-danger">Eliminar</button>
              </form>
            </span>
          </div>
          <div class="contact-list__details" id="<?= h($detailsId) ?>" role="row" aria-hidden="true" hidden>
            <div class="contact-list__cell contact-list__cell--details" role="cell" data-label="Detalles">
              <dl class="contact-details">
                <div class="contact-details__item">
                  <dt>Nombre</dt>
                  <dd><?= h($contactName) ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Entidad</dt>
                  <dd><?= $entityName !== '' ? h($entityName) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Fecha del evento</dt>
                  <dd><?= $fechaEvento !== '' ? h($fechaEvento) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Título</dt>
                  <dd><?= $titulo !== '' ? h($titulo) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Cargo</dt>
                  <dd><?= $cargo !== '' ? h($cargo) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Celular</dt>
                  <dd><?= $telefono !== '' ? h($telefono) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Correo</dt>
                  <dd><?= $correo !== '' ? h($correo) : '—' ?></dd>
                </div>
                <div class="contact-details__item contact-details__item--full">
                  <dt>Nota</dt>
                  <dd><?= $nota !== '' ? nl2br(h($nota)) : '—' ?></dd>
                </div>
              </dl>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <nav class="pagination" aria-label="Paginación de contactos">
        <?php if ($page > 1): ?>
          <a href="<?= h(buildPageUrlContactos($prev, $filters, $perPage)) ?>" rel="prev">&laquo; Anterior</a>
        <?php else: ?>
          <span class="disabled" aria-disabled="true">&laquo; Anterior</span>
        <?php endif; ?>
        <span aria-live="polite">Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?></span>
        <?php if ($page < $pages): ?>
          <a href="<?= h(buildPageUrlContactos($next, $filters, $perPage)) ?>" rel="next">Siguiente &raquo;</a>
        <?php else: ?>
          <span class="disabled" aria-disabled="true">Siguiente &raquo;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
</section>
<div class="contact-modal" id="contacto-crear-modal" data-modal hidden aria-hidden="true">
  <div class="contact-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="nuevo-contacto-modal-title" tabindex="-1" data-modal-dialog>
    <div class="contact-modal__header">
      <h2 id="nuevo-contacto-modal-title" class="ent-title">Nuevo contacto</h2>
      <button type="button" class="contact-modal__close" data-modal-close aria-label="Cerrar">
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
    </div>
    <form method="post" action="/comercial/contactos" class="form ent-form contact-modal__form">
      <div class="form-row">
        <label for="modal-contacto-entidad">Entidad</label>
        <select id="modal-contacto-entidad" name="id_entidad" required data-focus-initial>
          <?php foreach ($entidades as $ent): ?>
            <option value="<?= h((string)$ent['id']) ?>"><?= h($ent['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="modal-contacto-nombre">Nombre</label>
        <input id="modal-contacto-nombre" type="text" name="nombre" placeholder="Juan Pérez" required>
      </div>
      <div class="form-row">
        <label for="modal-contacto-fecha">Fecha del evento</label>
        <input id="modal-contacto-fecha" type="date" name="fecha_evento" value="<?= h($today) ?>" required>
      </div>
      <div class="form-row">
        <label for="modal-contacto-titulo">Título</label>
        <input id="modal-contacto-titulo" type="text" name="titulo" placeholder="Gerente de ventas">
      </div>
      <div class="form-row">
        <label for="modal-contacto-cargo">Cargo</label>
        <input id="modal-contacto-cargo" type="text" name="cargo" placeholder="Director">
      </div>
      <div class="form-row">
        <label for="modal-contacto-telefono">Celular</label>
        <input id="modal-contacto-telefono" type="text" name="telefono" placeholder="0999999999" inputmode="numeric" pattern="[0-9]{10}" maxlength="10">
      </div>
      <div class="form-row">
        <label for="modal-contacto-correo">Correo</label>
        <input id="modal-contacto-correo" type="email" name="correo" placeholder="correo@dominio.com">
      </div>
      <div class="form-row">
        <label for="modal-contacto-nota">Nota</label>
        <textarea id="modal-contacto-nota" name="nota" placeholder="Observaciones adicionales"></textarea>
      </div>
      <div class="contact-modal__actions">
        <button class="btn btn-primary" type="submit">Crear contacto</button>
        <button class="btn btn-cancel" type="button" data-modal-cancel>Cancelar</button>
      </div>
    </form>
  </div>
</div>
<div class="contact-modal" id="contacto-editar-modal" data-modal hidden aria-hidden="true">
  <div class="contact-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="editar-contacto-modal-title" tabindex="-1" data-modal-dialog>
    <div class="contact-modal__header">
      <h2 id="editar-contacto-modal-title" class="ent-title">Editar contacto</h2>
      <button type="button" class="contact-modal__close" data-modal-close aria-label="Cerrar">
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
    </div>
    <form method="post" action="" class="form ent-form contact-modal__form" data-contact-edit-form data-action-base="/comercial/contactos/">
      <input type="hidden" name="id" value="" data-contact-id>
      <div class="form-row">
        <label for="modal-editar-contacto-entidad">Entidad</label>
        <select id="modal-editar-contacto-entidad" name="id_entidad" required>
          <?php foreach ($entidades as $ent): ?>
            <option value="<?= h((string)$ent['id']) ?>"><?= h($ent['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-nombre">Nombre</label>
        <input id="modal-editar-contacto-nombre" type="text" name="nombre" required data-focus-initial>
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-fecha">Fecha del evento</label>
        <input id="modal-editar-contacto-fecha" type="date" name="fecha_evento" required>
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-titulo">Título</label>
        <input id="modal-editar-contacto-titulo" type="text" name="titulo">
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-cargo">Cargo</label>
        <input id="modal-editar-contacto-cargo" type="text" name="cargo">
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-telefono">Celular</label>
        <input id="modal-editar-contacto-telefono" type="text" name="telefono" inputmode="numeric" pattern="[0-9]{10}" maxlength="10">
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-correo">Correo</label>
        <input id="modal-editar-contacto-correo" type="email" name="correo">
      </div>
      <div class="form-row">
        <label for="modal-editar-contacto-nota">Nota</label>
        <textarea id="modal-editar-contacto-nota" name="nota"></textarea>
      </div>
      <div class="contact-modal__actions">
        <button class="btn btn-primary" type="submit">Guardar cambios</button>
        <button class="btn btn-cancel" type="button" data-modal-cancel>Cancelar</button>
      </div>
    </form>
  </div>
</div>
<script src="/js/contactos-typeahead.js" defer></script>
