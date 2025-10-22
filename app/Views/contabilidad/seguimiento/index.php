<?php
use App\Services\Shared\Pagination;

if (!function_exists('cseg_list_h')) {
    function cseg_list_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$items        = isset($items) && is_array($items) ? $items : [];
$filters      = isset($filters) && is_array($filters) ? $filters : [];
$cooperativas = isset($cooperativas) && is_array($cooperativas) ? $cooperativas : [];
$tipos        = isset($tipos) && is_array($tipos) ? $tipos : [];
$medios       = isset($medios) && is_array($medios) ? $medios : [];
$resultados   = isset($resultados) && is_array($resultados) ? $resultados : [];
$toastData    = isset($toast) && is_array($toast) ? $toast : null;

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

$coopFiltro      = (string)($filters['coop'] ?? '');
$tipoFiltro      = (string)($filters['tipo'] ?? '');
$medioFiltro     = (string)($filters['medio'] ?? '');
$resultadoFiltro = (string)($filters['resultado'] ?? '');
$desdeFiltro     = (string)($filters['desde'] ?? '');
$hastaFiltro     = (string)($filters['hasta'] ?? '');
$ticketFiltro    = (string)($filters['ticket'] ?? '');
$qFiltro         = (string)($filters['q'] ?? '');

function buildContabSeguimientoUrl(int $pageNumber, array $filters, int $perPage): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return '/contabilidad/seguimiento' . ($queryString !== '' ? '?' . $queryString : '');
}
?>
<link rel="stylesheet" href="/css/contabilidad.css">
<section class="ent-list ent-seguimiento" aria-labelledby="contab-seguimiento-title">
  <?php if ($toastData && isset($toastData['message']) && $toastData['message'] !== ''): ?>
    <div
      id="ent-toast"
      class="ent-toast"
      role="status"
      aria-live="polite"
      <?php if (($toastData['variant'] ?? '') === 'error'): ?>
        style="background:#dc2626;"
      <?php endif; ?>
    >
      <?= cseg_list_h((string)$toastData['message']) ?>
    </div>
  <?php endif; ?>
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="contab-seguimiento-title" class="ent-title">Seguimiento contable</h1>
      <p class="ent-toolbar__caption">
        <?= cseg_list_h((string)$total) ?> gestiones · Página <?= cseg_list_h((string)$page) ?> de <?= cseg_list_h((string)max(1, $pages)) ?>
      </p>
    </div>
    <div class="ent-toolbar__actions">
      <a class="btn btn-primary" href="/contabilidad/seguimiento/crear">
        <span class="material-symbols-outlined" aria-hidden="true">add</span>
        Nueva gestión
      </a>
    </div>
  </header>

  <section class="seguimiento-card seguimiento-card--filters">
    <form class="seguimiento-filters" method="get" action="/contabilidad/seguimiento" role="search">
      <div class="seguimiento-filters__basic">
        <div class="seguimiento-filters__field">
          <label for="contab-seguimiento-desde">Desde</label>
          <input id="contab-seguimiento-desde" type="date" name="desde" value="<?= cseg_list_h($desdeFiltro) ?>">
        </div>
        <div class="seguimiento-filters__field">
          <label for="contab-seguimiento-hasta">Hasta</label>
          <input id="contab-seguimiento-hasta" type="date" name="hasta" value="<?= cseg_list_h($hastaFiltro) ?>">
        </div>
        <div class="seguimiento-filters__field">
          <label for="contab-seguimiento-entidad">Entidad</label>
          <select id="contab-seguimiento-entidad" name="coop">
            <option value="">Todas</option>
            <?php foreach ($cooperativas as $entidad): ?>
              <?php $id = isset($entidad['id']) ? (string)$entidad['id'] : ''; ?>
              <option value="<?= cseg_list_h($id) ?>" <?= $id === $coopFiltro ? 'selected' : '' ?>>
                <?= cseg_list_h($entidad['nombre'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="seguimiento-filters__field">
          <label for="contab-seguimiento-tipo">Tipo</label>
          <select id="contab-seguimiento-tipo" name="tipo">
            <option value="">Todos</option>
            <?php foreach ($tipos as $tipo): ?>
              <?php $nombre = (string)$tipo; ?>
              <option value="<?= cseg_list_h($nombre) ?>" <?= strcasecmp($nombre, $tipoFiltro) === 0 ? 'selected' : '' ?>>
                <?= cseg_list_h($nombre) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="seguimiento-filters__field">
          <label for="contab-seguimiento-medio">Medio</label>
          <select id="contab-seguimiento-medio" name="medio">
            <option value="">Todos</option>
            <?php foreach ($medios as $medio): ?>
              <?php $nombre = (string)$medio; ?>
              <option value="<?= cseg_list_h($nombre) ?>" <?= strcasecmp($nombre, $medioFiltro) === 0 ? 'selected' : '' ?>>
                <?= cseg_list_h($nombre) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="seguimiento-filters__field">
          <label for="contab-seguimiento-resultado">Resultado</label>
          <select id="contab-seguimiento-resultado" name="resultado">
            <option value="">Todos</option>
            <?php foreach ($resultados as $resultado): ?>
              <?php $nombre = (string)$resultado; ?>
              <option value="<?= cseg_list_h($nombre) ?>" <?= strcasecmp($nombre, $resultadoFiltro) === 0 ? 'selected' : '' ?>>
                <?= cseg_list_h($nombre) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="seguimiento-filters__actions">
          <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            Buscar
          </button>
          <button type="button" class="btn btn-outline" data-action="seguimiento-reset">
            <span class="material-symbols-outlined" aria-hidden="true">undo</span>
            Limpiar
          </button>
        </div>
      </div>
      <div class="seguimiento-filters__advanced seguimiento-filters__field--wide">
        <label for="contab-seguimiento-ticket">Ticket</label>
        <input
          id="contab-seguimiento-ticket"
          type="text"
          name="ticket"
          value="<?= cseg_list_h($ticketFiltro) ?>"
          placeholder="Código o asunto del ticket">
      </div>
      <div class="seguimiento-filters__advanced seguimiento-filters__field--wide">
        <label for="contab-seguimiento-q">Descripción</label>
        <input
          id="contab-seguimiento-q"
          type="text"
          name="q"
          value="<?= cseg_list_h($qFiltro) ?>"
          placeholder="Buscar en la descripción o resultados">
      </div>
    </form>
  </section>

  <section class="seguimiento-results" aria-live="polite">
    <?php if (!$items): ?>
      <div class="seguimiento-empty">
        <span class="material-symbols-outlined" aria-hidden="true">inbox</span>
        <p>No hay gestiones contables registradas con los filtros seleccionados.</p>
      </div>
    <?php else: ?>
      <div class="seguimiento-cards">
        <?php foreach ($items as $item): ?>
          <?php
            $fechaInicio = isset($item['fecha_inicio']) ? (string)$item['fecha_inicio'] : '';
            $fechaFin    = isset($item['fecha_fin']) ? (string)$item['fecha_fin'] : '';
            $fechaInicioTexto = $fechaInicio !== '' && strtotime($fechaInicio) ? date('d/m/Y', strtotime($fechaInicio)) : '';
            $fechaFinTexto = $fechaFin !== '' && strtotime($fechaFin) ? date('d/m/Y', strtotime($fechaFin)) : 'En seguimiento';
            $ticketCodigo = trim((string)($item['ticket_codigo'] ?? ''));
            $resultado = trim((string)($item['resultado'] ?? ''));
          ?>
          <article class="seguimiento-card">
            <header class="seguimiento-card__header">
              <h2><?= cseg_list_h($item['cooperativa'] ?? 'Entidad') ?></h2>
              <span class="seguimiento-chip"><?= cseg_list_h($item['tipo'] ?? '') ?></span>
            </header>
            <div class="seguimiento-card__body">
              <dl>
                <div>
                  <dt>Periodo</dt>
                  <dd><?= cseg_list_h($fechaInicioTexto) ?> &rarr; <?= cseg_list_h($fechaFinTexto) ?></dd>
                </div>
                <?php if (!empty($item['medio'])): ?>
                  <div>
                    <dt>Medio</dt>
                    <dd><?= cseg_list_h((string)$item['medio']) ?></dd>
                  </div>
                <?php endif; ?>
                <div>
                  <dt>Descripción</dt>
                  <dd><?= cseg_list_h((string)$item['descripcion']) ?></dd>
                </div>
                <?php if ($resultado !== ''): ?>
                  <div>
                    <dt>Resultado</dt>
                    <dd><?= cseg_list_h($resultado) ?></dd>
                  </div>
                <?php endif; ?>
                <?php if ($ticketCodigo !== ''): ?>
                  <div>
                    <dt>Ticket</dt>
                    <dd><span class="seguimiento-badge"><?= cseg_list_h($ticketCodigo) ?></span></dd>
                  </div>
                <?php endif; ?>
                <?php if (!empty($item['contacto_nombre'])): ?>
                  <div>
                    <dt>Contacto</dt>
                    <dd>
                      <?= cseg_list_h((string)$item['contacto_nombre']) ?>
                      <?php if (!empty($item['contacto_telefono'])): ?>
                        · <?= cseg_list_h((string)$item['contacto_telefono']) ?>
                      <?php endif; ?>
                    </dd>
                  </div>
                <?php endif; ?>
              </dl>
            </div>
            <footer class="seguimiento-card__footer">
              <span class="seguimiento-meta">
                Registrado por <?= cseg_list_h((string)($item['usuario'] ?? '')) ?>
              </span>
              <?php if (!empty($item['ticket_prioridad'])): ?>
                <span class="seguimiento-meta seguimiento-meta--<?= strtolower((string)$item['ticket_prioridad']) ?>">
                  Ticket: <?= cseg_list_h((string)$item['ticket_prioridad']) ?>
                </span>
              <?php endif; ?>
            </footer>
          </article>
        <?php endforeach; ?>
      </div>

      <nav class="pagination" aria-label="Paginación de seguimiento">
        <?php if ($page > 1): ?>
          <a href="<?= cseg_list_h(buildContabSeguimientoUrl($prev, $filters, $perPage)) ?>" rel="prev">&laquo; Anterior</a>
        <?php else: ?>
          <span class="disabled">&laquo; Anterior</span>
        <?php endif; ?>
        <span>Página <?= cseg_list_h((string)$page) ?> de <?= cseg_list_h((string)max(1, $pages)) ?></span>
        <?php if ($page < $pages): ?>
          <a href="<?= cseg_list_h(buildContabSeguimientoUrl($next, $filters, $perPage)) ?>" rel="next">Siguiente &raquo;</a>
        <?php else: ?>
          <span class="disabled">Siguiente &raquo;</span>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  </section>
</section>
<script src="/js/contabilidad-seguimiento.js" defer></script>
