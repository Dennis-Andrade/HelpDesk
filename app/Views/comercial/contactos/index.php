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

  <section class="card ent-container" aria-labelledby="nuevo-contacto-title">
    <h2 id="nuevo-contacto-title" class="ent-title">Nuevo contacto</h2>
    <form method="post" action="/comercial/contactos" class="form ent-form">
      <div class="form-row">
        <label for="contacto-entidad">Entidad</label>
        <select id="contacto-entidad" name="id_entidad" required>
          <?php foreach ($entidades as $ent): ?>
            <option value="<?= h((string)$ent['id']) ?>"><?= h($ent['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label for="contacto-nombre">Nombre</label>
        <input id="contacto-nombre" type="text" name="nombre" placeholder="Juan Pérez" required>
      </div>
      <div class="form-row">
        <label for="contacto-titulo">Título</label>
        <input id="contacto-titulo" type="text" name="titulo" placeholder="Gerente de ventas">
      </div>
      <div class="form-row">
        <label for="contacto-cargo">Cargo</label>
        <input id="contacto-cargo" type="text" name="cargo" placeholder="Director">
      </div>
      <div class="form-row">
        <label for="contacto-telefono">Teléfono</label>
        <input id="contacto-telefono" type="text" name="telefono" placeholder="+593 9 9999 9999">
      </div>
      <div class="form-row">
        <label for="contacto-correo">Correo</label>
        <input id="contacto-correo" type="email" name="correo" placeholder="correo@dominio.com">
      </div>
      <div class="form-row">
        <label for="contacto-nota">Nota</label>
        <textarea id="contacto-nota" name="nota" placeholder="Observaciones adicionales"></textarea>
      </div>
      <div class="form-actions ent-actions">
        <button class="btn btn-primary" type="submit">Crear contacto</button>
      </div>
    </form>
  </section>

  <section class="ent-container ent-contactos-list" aria-labelledby="contactos-listado-title">
    <h2 id="contactos-listado-title" class="ent-title">Listado de contactos</h2>
    <form class="ent-search ent-search--stack" action="/comercial/contactos" method="get" role="search">
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
      <button class="btn btn-outline" type="submit">Buscar</button>
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
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Cargo</span>
          <span class="contact-list__cell contact-list__cell--header" role="columnheader">Teléfono</span>
          <span class="contact-list__cell contact-list__cell--header contact-list__cell--actions" role="columnheader">Acciones</span>
        </div>
        <?php foreach ($items as $index => $row): ?>
          <?php
            $contactId   = (int)($row['id'] ?? 0);
            $contactName = $row['nombre'] ?? 'Contacto';
            $entityName  = $row['entidad_nombre'] ?? '';
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
            <span class="contact-list__cell" data-label="Cargo" role="cell"><?= $cargo !== '' ? h($cargo) : '—' ?></span>
            <span class="contact-list__cell" data-label="Teléfono" role="cell"><?= $telefono !== '' ? h($telefono) : '—' ?></span>
            <span class="contact-list__cell contact-list__cell--actions" data-label="Acciones" role="cell">
              <a class="btn btn-primary" href="/comercial/contactos/editar?id=<?= h((string)$contactId) ?>" target="_blank" rel="noopener">Editar</a>
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
                  <dt>Título</dt>
                  <dd><?= $titulo !== '' ? h($titulo) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Cargo</dt>
                  <dd><?= $cargo !== '' ? h($cargo) : '—' ?></dd>
                </div>
                <div class="contact-details__item">
                  <dt>Teléfono</dt>
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
<script src="/js/contactos-typeahead.js" defer></script>
