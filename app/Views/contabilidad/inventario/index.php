<?php
if (!function_exists('cinv_h')) {
    function cinv_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$items    = isset($items) && is_array($items) ? $items : [];
$estados  = isset($estados) && is_array($estados) ? $estados : [];
$filters  = isset($filters) && is_array($filters) ? $filters : [];

$estadoFiltro       = trim((string)($filters['estado'] ?? ''));
$busquedaFiltro     = trim((string)($filters['q'] ?? ''));
$responsableFiltro  = trim((string)($filters['responsable'] ?? ''));
$desdeFiltro        = trim((string)($filters['desde'] ?? ''));
$hastaFiltro        = trim((string)($filters['hasta'] ?? ''));

function cinvEstadoClase(string $estado): string
{
    $map = [
        'nuevo'    => 'inventario-status inventario-status--nuevo',
        'reparado' => 'inventario-status inventario-status--reparado',
        'dañado'   => 'inventario-status inventario-status--danado',
        'danado'   => 'inventario-status inventario-status--danado',
    ];
    $key = strtolower($estado);
    return $map[$key] ?? 'inventario-status';
}
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="ent-container inventario" aria-labelledby="inventario-title">
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="inventario-title" class="ent-title">Inventario de equipos</h1>
      <p class="ent-toolbar__caption"><?= cinv_h((string)count($items)) ?> equipos registrados</p>
    </div>
    <div class="ent-toolbar__actions">
      <a class="btn btn-primary" href="/contabilidad/inventario/crear">
        <span class="material-symbols-outlined" aria-hidden="true">add</span>
        Registrar equipo
      </a>
    </div>
  </header>

  <form class="inventario-filters" method="get" action="/contabilidad/inventario">
    <div class="inventario-filters__field">
      <label for="inventario-q">Nombre o código</label>
      <input
        id="inventario-q"
        type="text"
        name="q"
        value="<?= cinv_h($busquedaFiltro) ?>"
        placeholder="Ej.: Laptop, EQ-2025-001"
        autocomplete="off"
        spellcheck="false"
        data-typeahead="generic"
        data-suggest-url="/contabilidad/inventario/sugerencias"
        data-suggest-min="2"
        data-suggest-value="term"
        data-suggest-label="label"
        data-suggest-merge="true"
        list="inventario-search-suggestions">
      <datalist id="inventario-search-suggestions"></datalist>
    </div>
    <div class="inventario-filters__field">
      <label for="inventario-estado">Estado</label>
      <select id="inventario-estado" name="estado">
        <option value="">Todos</option>
        <?php foreach ($estados as $opcion): ?>
          <?php $nombre = (string)$opcion; ?>
          <option value="<?= cinv_h($nombre) ?>" <?= strcasecmp($nombre, $estadoFiltro) === 0 ? 'selected' : '' ?>>
            <?= cinv_h($nombre) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="inventario-filters__field">
      <label for="inventario-responsable">Responsable</label>
      <input id="inventario-responsable" type="text" name="responsable" value="<?= cinv_h($responsableFiltro) ?>" placeholder="Nombre del responsable">
    </div>
    <div class="inventario-filters__field">
      <label for="inventario-desde">Desde</label>
      <input id="inventario-desde" type="date" name="desde" value="<?= cinv_h($desdeFiltro) ?>">
    </div>
    <div class="inventario-filters__field">
      <label for="inventario-hasta">Hasta</label>
      <input id="inventario-hasta" type="date" name="hasta" value="<?= cinv_h($hastaFiltro) ?>">
    </div>
    <div class="inventario-filters__actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-outlined" aria-hidden="true">search</span>
        Buscar
      </button>
      <a class="btn btn-outline" href="/contabilidad/inventario">
        <span class="material-symbols-outlined" aria-hidden="true">undo</span>
        Limpiar
      </a>
    </div>
  </form>

  <?php if (empty($items)): ?>
    <div class="seguimiento-empty">
      <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
      <p>No se han registrado equipos con el filtro seleccionado.</p>
    </div>
  <?php else: ?>
    <div class="inventario-grid">
      <?php foreach ($items as $equipo): ?>
        <?php
          $estadoLabel = (string)($equipo['estado'] ?? '');
          $estadoClase = cinvEstadoClase($estadoLabel);
          $fechaEntrega = isset($equipo['fecha_entrega']) && strtotime((string)$equipo['fecha_entrega'])
            ? date('d/m/Y', strtotime((string)$equipo['fecha_entrega']))
            : '';
        ?>
        <article class="inventario-card">
          <header>
            <h2><?= cinv_h($equipo['nombre'] ?? 'Equipo') ?></h2>
            <span class="<?= cinv_h($estadoClase) ?>"><?= cinv_h($estadoLabel) ?></span>
          </header>
          <div class="inventario-card__body">
            <p class="inventario-card__codigo"><?= cinv_h($equipo['codigo'] ?? '') ?></p>
            <?php if (!empty($equipo['responsable'])): ?>
              <p><strong>Responsable:</strong> <?= cinv_h($equipo['responsable']) ?></p>
            <?php endif; ?>
            <?php if ($fechaEntrega !== ''): ?>
              <p><strong>Fecha de entrega:</strong> <?= cinv_h($fechaEntrega) ?></p>
            <?php endif; ?>
          </div>
          <footer>
            <button
              type="button"
              class="btn btn-outline"
              data-inventario-detalle
              data-equipo-id="<?= cinv_h((string)($equipo['id'] ?? 0)) ?>">
              <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
              Ver
            </button>
            <?php if (!empty($equipo['documento_path'])): ?>
              <a class="btn btn-sm" href="/storage/<?= cinv_h((string)$equipo['documento_path']) ?>" target="_blank" rel="noopener">
                <span class="material-symbols-outlined" aria-hidden="true">download</span>
                Archivo
              </a>
            <?php endif; ?>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<div class="modal-overlay" data-inventario-modal hidden>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="inventario-modal-title">
    <header class="modal-header">
      <h2 id="inventario-modal-title">Detalle del equipo</h2>
      <button type="button" class="btn btn-sm" data-inventario-close>
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
    </header>
    <section class="modal-body" data-inventario-contenido>
      <p>Cargando...</p>
    </section>
  </div>
</div>

<script src="/js/search-typeahead.js" defer></script>
<script src="/js/contabilidad-inventario.js" defer></script>
