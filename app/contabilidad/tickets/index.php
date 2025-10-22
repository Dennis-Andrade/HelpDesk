<?php
use App\Services\Shared\Pagination;

if (!function_exists('ctk_list_h')) {
    function ctk_list_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$items        = isset($items) && is_array($items) ? $items : [];
$filters      = isset($filters) && is_array($filters) ? $filters : [];
$cooperativas = isset($cooperativas) && is_array($cooperativas) ? $cooperativas : [];
$categorias   = isset($categorias) && is_array($categorias) ? $categorias : [];
$prioridades  = isset($prioridades) && is_array($prioridades) ? $prioridades : [];
$estados      = isset($estados) && is_array($estados) ? $estados : [];

$page    = isset($page) ? (int)$page : 1;
$perPage = isset($perPage) ? (int)$perPage : 10;
$total   = isset($total) ? (int)$total : 0;

$pagination = Pagination::fromRequest([
    'page'    => $page,
    'perPage' => $perPage,
], 1, max(1, $perPage), $total);

$page    = $pagination->page;
$perPage = $pagination->perPage;
$pages   = $pagination->pages();
$prev    = max(1, $page - 1);
$next    = min($pages, $page + 1);

function buildTicketUrl(int $page, array $filters, int $perPage): string
{
    $query = array_merge($filters, [
        'page'    => $page,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return '/contabilidad/tickets' . ($queryString !== '' ? '?' . $queryString : '');
}
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="ent-container" aria-labelledby="tickets-contables-title">
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="tickets-contables-title" class="ent-title">Solicitudes contables</h1>
      <p class="ent-toolbar__caption">
        <?= ctk_list_h((string)$total) ?> solicitudes · Página <?= ctk_list_h((string)$page) ?> de <?= ctk_list_h((string)max(1, $pages)) ?>
      </p>
    </div>
    <div class="ent-toolbar__actions">
      <a class="btn btn-primary" href="/contabilidad/tickets/crear">
        <span class="material-symbols-outlined" aria-hidden="true">add</span>
        Nueva solicitud
      </a>
    </div>
  </header>

  <div class="seguimiento-card seguimiento-card--filters contab-tickets-filters">
    <form class="seguimiento-filters" method="get" action="/contabilidad/tickets" role="search">
      <div class="seguimiento-filters__field">
        <label for="tickets-estado">Estado</label>
        <select id="tickets-estado" name="estado">
          <option value="">Todos</option>
          <?php foreach ($estados as $estado): ?>
            <?php $nombre = (string)$estado; ?>
            <option value="<?= ctk_list_h($nombre) ?>" <?= strcasecmp($nombre, (string)($filters['estado'] ?? '')) === 0 ? 'selected' : '' ?>>
              <?= ctk_list_h($nombre) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="seguimiento-filters__field">
        <label for="tickets-prioridad">Prioridad</label>
        <select id="tickets-prioridad" name="prioridad">
          <option value="">Todas</option>
          <?php foreach ($prioridades as $prioridad): ?>
            <?php $nombre = (string)$prioridad; ?>
            <option value="<?= ctk_list_h($nombre) ?>" <?= strcasecmp($nombre, (string)($filters['prioridad'] ?? '')) === 0 ? 'selected' : '' ?>>
              <?= ctk_list_h($nombre) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="seguimiento-filters__field">
        <label for="tickets-categoria">Categoría</label>
        <select id="tickets-categoria" name="categoria">
          <option value="">Todas</option>
          <?php foreach ($categorias as $categoria): ?>
            <?php $nombre = (string)$categoria; ?>
            <option value="<?= ctk_list_h($nombre) ?>" <?= strcasecmp($nombre, (string)($filters['categoria'] ?? '')) === 0 ? 'selected' : '' ?>>
              <?= ctk_list_h($nombre) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="seguimiento-filters__field">
        <label for="tickets-entidad">Entidad</label>
        <select id="tickets-entidad" name="coop">
          <option value="">Todas</option>
          <?php foreach ($cooperativas as $entidad): ?>
            <?php $id = isset($entidad['id']) ? (string)$entidad['id'] : ''; ?>
            <option value="<?= ctk_list_h($id) ?>" <?= $id === (string)($filters['coop'] ?? '') ? 'selected' : '' ?>>
              <?= ctk_list_h($entidad['nombre'] ?? '') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="seguimiento-filters__field seguimiento-filters__field--wide">
        <label for="tickets-q">Buscar</label>
        <input
          id="tickets-q"
          type="text"
          name="q"
          value="<?= ctk_list_h((string)($filters['q'] ?? '')) ?>"
          placeholder="Código, asunto o descripción"
          autocomplete="off"
          spellcheck="false"
          data-typeahead="generic"
          data-suggest-url="/contabilidad/seguimiento/sugerencias/tickets"
          data-suggest-min="2"
          data-suggest-value="codigo"
          data-suggest-label="titulo"
          data-suggest-merge="true"
          list="contab-tickets-suggestions">
        <datalist id="contab-tickets-suggestions"></datalist>
      </div>
      <div class="seguimiento-filters__actions">
        <button type="submit" class="btn btn-primary">
          <span class="material-symbols-outlined" aria-hidden="true">search</span>
          Buscar
        </button>
        <a class="btn btn-outline" href="/contabilidad/tickets">
          <span class="material-symbols-outlined" aria-hidden="true">undo</span>
          Limpiar
        </a>
      </div>
    </form>
  </div>

  <?php if (!$items): ?>
    <div class="seguimiento-empty">
      <span class="material-symbols-outlined" aria-hidden="true">inbox</span>
      <p>No hay solicitudes contables registradas.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Código</th>
            <th>Entidad</th>
            <th>Categoría</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th>Registrado</th>
            <th class="table-actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $row): ?>
            <tr>
              <td><?= ctk_list_h($row['codigo'] ?? '') ?></td>
              <td><?= ctk_list_h($row['cooperativa'] ?? '') ?></td>
              <td><?= ctk_list_h($row['categoria'] ?? '') ?></td>
              <td><span class="badge badge--<?= strtolower((string)($row['prioridad'] ?? '')) ?>"><?= ctk_list_h($row['prioridad'] ?? '') ?></span></td>
              <td><span class="badge badge--<?= strtolower((string)($row['estado'] ?? '')) ?>"><?= ctk_list_h($row['estado'] ?? '') ?></span></td>
              <td><?= ctk_list_h(isset($row['fecha_apertura']) ? date('d/m/Y', strtotime((string)$row['fecha_apertura'])) : '') ?></td>
              <td class="table-actions">
                <a class="btn btn-sm" href="/contabilidad/tickets/editar?id=<?= ctk_list_h((string)($row['id'] ?? 0)) ?>">Editar</a>
                <form method="post" action="/contabilidad/tickets/eliminar" onsubmit="return confirm('¿Eliminar esta solicitud?');">
                  <input type="hidden" name="id" value="<?= ctk_list_h((string)($row['id'] ?? 0)) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <nav class="pagination" aria-label="Paginación de tickets">
      <?php if ($page > 1): ?>
        <a href="<?= ctk_list_h(buildTicketUrl($prev, $filters, $perPage)) ?>" rel="prev">&laquo; Anterior</a>
      <?php else: ?>
        <span class="disabled">&laquo; Anterior</span>
      <?php endif; ?>
      <span>Página <?= ctk_list_h((string)$page) ?> de <?= ctk_list_h((string)max(1, $pages)) ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= ctk_list_h(buildTicketUrl($next, $filters, $perPage)) ?>" rel="next">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="disabled">Siguiente &raquo;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>
<script src="/js/search-typeahead.js" defer></script>
