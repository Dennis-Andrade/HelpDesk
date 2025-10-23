<?php
use App\Services\Shared\Pagination;
/** @var array $items  Lista de entidades */
/** @var int   $total  Total de registros */
/** @var int   $page   Página actual */
/** @var int   $perPage Elementos por página */
/** @var string $q     Búsqueda actual */
/** @var array $filters Filtros activos */
/** @var string|null $toastMessage Mensaje de éxito */

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatSegment($row): string
{
    if (isset($row['segmento_nombre']) && $row['segmento_nombre'] !== '') {
        return h($row['segmento_nombre']);
    }
    if (isset($row['segmento']) && $row['segmento'] !== '') {
        return h($row['segmento']);
    }
    if (!empty($row['nombre_segmento'])) {
        return h($row['nombre_segmento']);
    }
    if (!empty($row['id_segmento'])) {
        return h('Segmento ' . (int)$row['id_segmento']);
    }
    return 'No especificado';
}

function formatLocation($row): string
{
    $provincia = trim((string)($row['provincia_nombre'] ?? $row['provincia'] ?? ''));
    $canton    = trim((string)($row['canton_nombre'] ?? $row['canton'] ?? ''));
    if ($provincia === '' && $canton === '') {
        return 'No especificado';
    }
    if ($provincia === '') {
        return h($canton);
    }
    if ($canton === '') {
        return h($provincia);
    }
    return h($provincia . ' – ' . $canton);
}

function gatherPhones($row): array
{
    $phones = [];

    if (isset($row['telefonos']) && is_array($row['telefonos'])) {
        foreach ($row['telefonos'] as $value) {
            if (!is_scalar($value)) { continue; }
            $phones[] = trim((string)$value);
        }
    }

    foreach (['telefono_fijo', 'telefono_fijo_1', 'telefono', 'telefono_movil', 'celular'] as $key) {
        if (!empty($row[$key])) {
            $phones[] = trim((string)$row[$key]);
        }
    }

    $phones = array_values(array_unique(array_filter($phones, static function ($v) {
        return $v !== '';
    })));

    return $phones;
}

function gatherServices($row): array
{
    $raw = $row['servicios'] ?? [];
    if (is_string($raw)) {
        $parts = array_map('trim', explode(',', $raw));
        return array_values(array_filter($parts, static function ($v) {
            return $v !== '';
        }));
    }
    if (!is_array($raw)) {
        return [];
    }

    $labels = [];
    foreach ($raw as $svc) {
        if (is_array($svc) && isset($svc['nombre_servicio'])) {
            $labels[] = trim((string)$svc['nombre_servicio']);
        } elseif (is_scalar($svc)) {
            $labels[] = trim((string)$svc);
        }
    }

    return array_values(array_filter($labels, static function ($v) {
        return $v !== '';
    }));
}

function primaryEmail($row): ?string
{
    if (isset($row['email']) && is_array($row['email'])) {
        foreach ($row['email'] as $value) {
            if (!is_scalar($value)) { continue; }
            $trimmed = trim((string)$value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }
    }

    $email = trim((string)($row['email'] ?? ''));
    return $email === '' ? null : $email;
}

function formatDireccion(array $row): string
{
    $calle = trim((string)($row['direccion_calle'] ?? ''));
    $inter = trim((string)($row['direccion_interseccion'] ?? ''));
    if ($calle !== '' && $inter !== '') {
        return h($calle . ' y ' . $inter);
    }
    if ($calle !== '') {
        return h($calle);
    }
    if ($inter !== '') {
        return h($inter);
    }
    $facturacionDir = trim((string)($row['direccion_facturacion'] ?? ''));
    if ($facturacionDir !== '') {
        return h($facturacionDir);
    }
    return 'No especificado';
}

function formatCurrencyValue($value): string
{
    if ($value === null) {
        return 'No registrado';
    }
    $amount = (float)$value;
    if (abs($amount) < 0.005) {
        return '$0.00';
    }
    return '$' . number_format($amount, 2, '.', ',');
}

function formatSicLicencias(array $row): string
{
    $licencias = isset($row['sic_licencias']) ? (int)$row['sic_licencias'] : 0;
    if ($licencias <= 0) {
        return 'Sin licencias registradas';
    }
    $label = $licencias === 1 ? 'licencia' : 'licencias';
    return h((string)$licencias . ' ' . $label);
}

function formatFechaRegistro(?string $value): string
{
    if ($value === null || $value === '') {
        return 'Sin registro';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return h($value);
    }
    return date('d/m/Y', $timestamp);
}

$toastMessage = isset($toastMessage) && $toastMessage !== '' ? (string)$toastMessage : null;
$modulePrefix = isset($modulePrefix) && is_string($modulePrefix) ? trim($modulePrefix, '/') : 'comercial';
$modulePrefix = $modulePrefix !== '' ? $modulePrefix : 'comercial';
$basePath     = '/' . $modulePrefix . '/entidades';
$filters = isset($filters) && is_array($filters) ? $filters : [];
if ($q !== '') {
    $filters['q'] = $q;
} elseif (isset($filters['q'])) {
    unset($filters['q']);
}

$filters = array_filter($filters, static function ($value) {
    if (is_array($value)) {
        foreach ($value as $item) {
            if ($item !== '' && $item !== null) {
                return true;
            }
        }
        return false;
    }
    return $value !== '' && $value !== null;
});

$pagination = Pagination::fromRequest([
    'page'    => (int)$page,
    'perPage' => (int)$perPage,
], 1, (int)$perPage, (int)$total);

$page    = $pagination->page;
$perPage = $pagination->perPage;
$pages   = $pagination->pages();
$prev    = max(1, $page - 1);
$next    = min($pages, $page + 1);

function buildPageUrl(int $pageNumber, array $filters, int $perPage, string $basePath): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);

    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

    return $basePath . ($queryString !== '' ? '?' . $queryString : '');
}
?>
<section class="ent-list ent-list--cards" aria-labelledby="ent-cards-title">
  <?php if ($toastMessage !== null): ?>
    <div id="ent-toast" class="ent-toast" role="status" aria-live="polite"><?= h($toastMessage) ?></div>
  <?php endif; ?>
  <header class="ent-toolbar" role="search">
    <div class="ent-toolbar__lead">
      <h1 id="ent-cards-title" class="ent-title">Entidades</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)(int)$total) ?> entidades · Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?>
      </p>
    </div>
    <form class="ent-search" action="<?= h($basePath) ?>" method="get" data-suggest-url="<?= h($basePath) ?>/sugerencias">
      <label for="ent-search-input">Buscar por nombre o RUC</label>
      <input id="ent-search-input" type="text" name="q" value="<?= h($q) ?>" aria-describedby="ent-search-help" placeholder="Entidad o RUC" autocomplete="off" spellcheck="false" aria-controls="ent-search-suggestions" aria-autocomplete="list" aria-expanded="false">
      <div id="ent-search-suggestions" class="ent-search__suggestions" data-min-chars="3" role="listbox" aria-label="Sugerencias de entidades" hidden></div>
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
      <span id="ent-search-help" class="ent-search__help">Escribe al menos 3 caracteres</span>
      <button class="btn btn-outline" type="submit">Buscar</button>
      <a class="btn btn-primary" href="<?= h($basePath) ?>/crear">Nueva entidad</a>
    </form>
  </header>

  <?php if (empty($items)): ?>
    <div class="card" role="status" aria-live="polite">No se encontraron entidades.</div>
  <?php else: ?>
    <ul class="ent-cards-grid" role="list">
      <?php foreach ($items as $index => $row): ?>
        <?php
          $entityId   = (int)($row['id'] ?? $row['id_entidad'] ?? 0);
          $cardTitle  = $row['nombre'] ?? 'Entidad';
          $phones     = gatherPhones($row);
          $services   = gatherServices($row);
          $serviceCount = isset($row['servicios_count']) ? (int)$row['servicios_count'] : count($services);
          $rawLogoPath = isset($row['logo_path']) ? trim((string)$row['logo_path']) : '';
          $logoPath = $rawLogoPath !== '' ? $rawLogoPath : null;
          if (function_exists('mb_substr')) {
            $logoInitial = mb_strtoupper((string)mb_substr((string)$cardTitle, 0, 1));
          } else {
            $logoInitial = strtoupper((string)substr((string)$cardTitle, 0, 1));
          }
          if ($logoInitial === '') {
            $logoInitial = 'E';
          }
        ?>
        <li class="ent-cards-grid__item" role="listitem">
          <article class="ent-card" aria-labelledby="ent-card-title-<?= h((string)$entityId) ?>">
            <header class="ent-card-head">
              <div class="ent-card-icon">
                <?php if ($logoPath !== null): ?>
                  <img src="/storage/<?= h($logoPath) ?>" alt="Logo de <?= h($cardTitle) ?>">
                <?php else: ?>
                  <span class="ent-card-icon-placeholder" aria-hidden="true"><?= h($logoInitial) ?></span>
                <?php endif; ?>
              </div>
              <h2 id="ent-card-title-<?= h((string)$entityId) ?>" class="ent-card-title"><?= h($cardTitle) ?></h2>
              <span class="ent-badge" aria-label="Servicios activos">
                <?= h((string)$serviceCount) ?> servicios
              </span>
            </header>
            <div class="ent-card-body">
              <div class="ent-card-row">
                <span class="ent-card-label">Segmento</span>
                <span class="ent-card-value"><?= formatSegment($row) ?></span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Provincia / Cantón</span>
                <span class="ent-card-value"><?= formatLocation($row) ?></span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Dirección específica</span>
                <span class="ent-card-value"><?= formatDireccion($row) ?></span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Teléfonos</span>
                <span class="ent-card-value">
                  <?php if (empty($phones)): ?>
                    No especificado
                  <?php else: ?>
                    <ul class="ent-card-phones" aria-label="Teléfonos de contacto">
                      <?php foreach ($phones as $phone): ?>
                        <li><?= h($phone) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Correo</span>
                <span class="ent-card-value">
                  <?php $mail = primaryEmail($row); ?>
                  <?= $mail === null ? 'No especificado' : h($mail) ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Servicios</span>
                <span class="ent-card-value">
                  <?php if (empty($services)): ?>
                    <span class="ent-chip ent-chip--empty">Sin registros</span>
                  <?php else: ?>
                    <span class="ent-badge-wrap" role="list">
                      <?php foreach ($services as $svc): ?>
                        <span class="ent-badge ent-badge--secondary" role="listitem"><?= h($svc) ?></span>
                      <?php endforeach; ?>
                    </span>
                  <?php endif; ?>
                </span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Facturación total</span>
                <span class="ent-card-value"><?= formatCurrencyValue($row['facturacion_total'] ?? null) ?></span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">SIC · Licencias</span>
                <span class="ent-card-value"><?= formatSicLicencias($row) ?></span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Registrada el</span>
                <span class="ent-card-value"><?= formatFechaRegistro($row['fecha_registro'] ?? null) ?></span>
              </div>
            </div>
            <footer class="ent-card-actions">
              <button type="button"
                      class="btn btn-outline ent-card-view js-entidad-view"
                      data-entidad-id="<?= h((string)$entityId) ?>"
                      aria-haspopup="dialog"
                      aria-controls="ent-card-modal"
                      aria-label="Ver detalle de <?= h($cardTitle) ?>">
                <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                <span>Ver</span>
              </button>
              <a class="btn btn-primary" href="<?= h($basePath) ?>/editar?id=<?= h((string)$entityId) ?>">Editar</a>
              <form method="post" action="<?= h($basePath) ?>/eliminar" class="ent-card-delete" aria-label="Eliminar <?= h($cardTitle) ?>">
                <input type="hidden" name="id" value="<?= h((string)$entityId) ?>">
                <button type="submit" class="btn btn-danger" onclick="return confirm('¿Deseas eliminar esta entidad?');">Eliminar</button>
              </form>
            </footer>
          </article>
        </li>
      <?php endforeach; ?>
    </ul>

    <nav class="pagination" aria-label="Paginación de entidades">
        <?php if ($page > 1): ?>
          <a href="<?= h(buildPageUrl($prev, $filters, $perPage, $basePath)) ?>" rel="prev">&laquo; Anterior</a>
      <?php else: ?>
        <span class="disabled" aria-disabled="true">&laquo; Anterior</span>
      <?php endif; ?>

      <span aria-live="polite">Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?></span>

        <?php if ($page < $pages): ?>
          <a href="<?= h(buildPageUrl($next, $filters, $perPage, $basePath)) ?>" rel="next">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="disabled" aria-disabled="true">Siguiente &raquo;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/_detalle_modal.php'; ?>
<script src="/js/entidades_cards.js" defer></script>
<script src="/js/entidades-typeahead.js" defer></script>

<?php if ($toastMessage !== null): ?>
<script>
(function(){
  var toast = document.getElementById('ent-toast');
  if(!toast){return;}
  setTimeout(function(){
    toast.style.transition = 'opacity .4s ease';
    toast.style.opacity = '0';
    setTimeout(function(){
      if(toast.parentNode){ toast.parentNode.removeChild(toast); }
    }, 400);
  }, 10000);
})();
</script>
<?php endif; ?>
