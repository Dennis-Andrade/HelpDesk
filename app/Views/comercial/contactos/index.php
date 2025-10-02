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
<section class="ent-list ent-list--cards" aria-labelledby="contactos-title">
  <header class="ent-toolbar" role="search">
    <div class="ent-toolbar__lead">
      <h1 id="contactos-title" class="ent-title">Agenda de contactos</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)(int)$total) ?> contactos · Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?>
      </p>
    </div>
    <form class="ent-search" action="/comercial/contactos" method="get">
      <label for="contactos-search-input">Buscar contacto</label>
      <input id="contactos-search-input" type="text" name="q" value="<?= h($q ?? '') ?>" aria-describedby="contactos-search-help" placeholder="Nombre...">
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
      <span id="contactos-search-help" class="ent-search__help">Escribe al menos 3 caracteres</span>
      <button class="btn btn-outline" type="submit">Buscar</button>
    </form>
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

  <?php if (empty($items)): ?>
    <div class="card" role="status" aria-live="polite">No se encontraron contactos.</div>
  <?php else: ?>
    <ul class="ent-cards-grid" role="list">
      <?php foreach ($items as $row): ?>
        <?php
          $contactId   = (int)($row['id'] ?? 0);
          $contactName = $row['nombre'] ?? 'Contacto';
          $entityName  = $row['entidad_nombre'] ?? '';
          $titulo      = $row['titulo'] ?? '';
          $cargo       = $row['cargo'] ?? '';
          $telefono    = $row['telefono'] ?? '';
          $correo      = $row['correo'] ?? '';
          $nota        = $row['nota'] ?? '';
        ?>
        <li class="ent-cards-grid__item" role="listitem">
          <article class="ent-card" aria-labelledby="contact-card-title-<?= h((string)$contactId) ?>">
            <header class="ent-card-head">
              <div class="ent-card-icon" aria-hidden="true">
                <span class="material-symbols-outlined" aria-hidden="true">person</span>
              </div>
              <h2 id="contact-card-title-<?= h((string)$contactId) ?>" class="ent-card-title">
                <?= h($contactName) ?>
              </h2>
              <span class="ent-badge" aria-label="Entidad asociada">
                <?= h($entityName) ?>
              </span>
            </header>
            <div class="ent-card-body">
              <div class="ent-card-row">
                <span class="ent-card-label">Título</span>
                <span class="ent-card-value">
                  <?= $titulo !== '' ? h($titulo) : '—' ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Cargo</span>
                <span class="ent-card-value">
                  <?= $cargo !== '' ? h($cargo) : '—' ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Teléfono</span>
                <span class="ent-card-value">
                  <?= $telefono !== '' ? h($telefono) : '—' ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Correo</span>
                <span class="ent-card-value">
                  <?= $correo !== '' ? h($correo) : '—' ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Nota</span>
                <span class="ent-card-value">
                  <?= $nota !== '' ? h($nota) : '—' ?>
                </span>
              </div>
            </div>
            <footer class="ent-card-actions">
              <form method="post" action="/comercial/contactos/<?= h((string)$contactId) ?>/eliminar" class="ent-card-delete" onsubmit="return confirm('¿Deseas eliminar este contacto?');">
                <button type="submit" class="btn btn-danger">Eliminar</button>
              </form>
            </footer>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>
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
