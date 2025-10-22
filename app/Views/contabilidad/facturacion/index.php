<?php
use App\Services\Shared\Pagination;

if (!function_exists('contab_h')) {
    function contab_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$items      = isset($items) && is_array($items) ? $items : [];
$filters    = isset($filters) && is_array($filters) ? $filters : [];
$toastData  = isset($toast) && is_array($toast) ? $toast : null;
$provincias = isset($provincias) && is_array($provincias) ? $provincias : [];

$pagination = Pagination::fromRequest([
    'page'    => (int)($page ?? 1),
    'perPage' => (int)($perPage ?? 10),
], 1, max(5, (int)($perPage ?? 10)), (int)($total ?? 0));

$page    = $pagination->page;
$perPage = $pagination->perPage;
$pages   = $pagination->pages();
$prev    = max(1, $page - 1);
$next    = min($pages, $page + 1);
$basePath = '/contabilidad/facturacion';

function buildFacturacionUrl(int $page, array $filters, int $perPage, string $basePath): string
{
    $query = array_merge($filters, [
        'page'    => $page,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return $basePath . ($queryString !== '' ? '?' . $queryString : '');
}

include __DIR__ . '/../../partials/breadcrumbs.php';
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="ent-container" aria-labelledby="facturacion-title">
  <?php if ($toastData && ($toastData['message'] ?? '') !== ''): ?>
    <div id="ent-toast" class="ent-toast" role="status" aria-live="polite">
      <?= contab_h((string)$toastData['message']) ?>
    </div>
  <?php endif; ?>

  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="facturacion-title" class="ent-title">Datos de facturación</h1>
      <p class="ent-toolbar__caption">Registros: <?= contab_h((string)$total) ?></p>
    </div>
  </header>

  <form class="ent-search ent-search--filters" action="<?= $basePath ?>" method="get" role="search">
    <div class="ent-search__field">
      <label for="facturacion-q">Buscar</label>
      <input
        id="facturacion-q"
        type="text"
        name="q"
        value="<?= contab_h($filters['q'] ?? '') ?>"
        placeholder="Entidad o correo"
        autocomplete="off"
        spellcheck="false"
        data-typeahead="generic"
        data-suggest-url="/contabilidad/entidades/sugerencias"
        data-suggest-min="3"
        data-suggest-value="term"
        data-suggest-label="label"
        list="facturacion-search-suggestions">
      <datalist id="facturacion-search-suggestions"></datalist>
    </div>
    <div class="ent-search__field">
      <label for="facturacion-provincia">Provincia</label>
      <select id="facturacion-provincia" name="provincia">
        <option value="">Todas</option>
        <?php foreach ($provincias as $provincia): ?>
          <?php $pid = (int)($provincia['id'] ?? 0); ?>
          <option value="<?= $pid ?>" <?= ((int)($filters['provincia'] ?? 0) === $pid) ? 'selected' : '' ?>>
            <?= contab_h($provincia['nombre'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="ent-search__actions ent-search__actions--filters">
      <button class="btn btn-outline" type="submit">Buscar</button>
      <a class="btn btn-ghost" href="<?= $basePath ?>">Limpiar</a>
    </div>
  </form>

  <?php if (empty($items)): ?>
    <div class="card" role="status" aria-live="polite">No hay registros de facturación con los filtros seleccionados.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Entidad</th>
            <th>Dirección</th>
            <th>Provincia</th>
            <th>Correo principal</th>
            <th>Teléfono contabilidad</th>
            <th class="table-actions">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><?= contab_h($item['cooperativa'] ?? '') ?></td>
              <td><?= contab_h($item['direccion'] ?? '—') ?></td>
              <td><?= contab_h($item['provincia_nombre'] ?? $item['provincia'] ?? '—') ?></td>
              <td><?= contab_h(($item['emails'][0] ?? '') !== '' ? $item['emails'][0] : '—') ?></td>
              <td><?= contab_h($item['contabilidad_telefono'] ?? '—') ?></td>
              <td class="table-actions">
                <a class="btn btn-sm" href="<?= $basePath ?>/editar?cooperativa=<?= contab_h((string)($item['id_cooperativa'] ?? 0)) ?>">Gestionar</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <nav class="pagination" aria-label="Paginación">
      <?php if ($page > 1): ?>
        <a href="<?= contab_h(buildFacturacionUrl($prev, $filters, $perPage, $basePath)) ?>" rel="prev">&laquo; Anterior</a>
      <?php else: ?>
        <span class="disabled">&laquo; Anterior</span>
      <?php endif; ?>
      <span>Página <?= contab_h((string)$page) ?> de <?= contab_h((string)$pages) ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= contab_h(buildFacturacionUrl($next, $filters, $perPage, $basePath)) ?>" rel="next">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="disabled">Siguiente &raquo;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>
<script src="/js/search-typeahead.js" defer></script>
