<?php
use App\Services\Shared\Pagination;
/** @var array $items  Lista de entidades */
/** @var int   $total  Total de registros */
/** @var int   $page   Página actual */
/** @var int   $perPage Elementos por página */
/** @var string $q     Búsqueda actual */
/** @var array $filters Filtros activos */

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatSegment($row): string
{
    if (isset($row['segmento']) && $row['segmento'] !== '') {
        return h($row['segmento']);
    }
    if (isset($row['segmento_nombre']) && $row['segmento_nombre'] !== '') {
        return h($row['segmento_nombre']);
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
    $provincia = trim((string)($row['provincia'] ?? $row['provincia_nombre'] ?? ''));
    $canton    = trim((string)($row['canton'] ?? $row['canton_nombre'] ?? ''));
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

    if (!empty($row['telefonos']) && is_array($row['telefonos'])) {
        foreach ($row['telefonos'] as $value) {
            if (!is_scalar($value)) {
                continue;
            }
            $trimmed = trim((string)$value);
            if ($trimmed === '') {
                continue;
            }
            $phones[] = $trimmed;
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
        $raw   = array_values(array_filter($parts, static function ($v) { return $v !== ''; }));
        return $raw;
    }
    if (!is_array($raw)) {
        $fallback = $row['servicios_text'] ?? '';
        if (is_string($fallback) && trim($fallback) !== '') {
            return array_map('trim', array_filter(explode(',', $fallback), static function ($v) { return $v !== ''; }));
        }
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
    return array_values(array_filter($labels, static function ($v) { return $v !== ''; }));
}

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

function buildPageUrl(int $pageNumber, array $filters, int $perPage): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);

    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

    return '/comercial/entidades' . ($queryString !== '' ? '?' . $queryString : '');
}
?>
<section class="ent-list ent-list--cards" aria-labelledby="ent-cards-title">
  <header class="ent-toolbar" role="search">
    <div class="ent-toolbar__lead">
      <h1 id="ent-cards-title" class="ent-title">Entidades financieras</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)(int)$total) ?> entidades · Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?>
      </p>
    </div>
    <a class="btn btn-primary" href="/comercial/entidades/crear">Nueva entidad</a>
    <form class="ent-search" action="/comercial/entidades" method="get">
      <label for="ent-search-input">Buscar por nombre o RUC</label>
      <input id="ent-search-input" type="text" name="q" value="<?= h($q) ?>" aria-describedby="ent-search-help" placeholder="Cooperativa...">
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
    </form>
  </header>

  <?php if (empty($items)): ?>
    <div class="card" role="status" aria-live="polite">No se encontraron entidades.</div>
  <?php else: ?>
    <ul class="ent-cards-grid" role="list">
      <?php foreach ($items as $index => $row): ?>
        <?php
          $entityId   = (int)($row['id_entidad'] ?? $row['id'] ?? 0);
          $cardTitle  = $row['nombre'] ?? 'Entidad';
          $phones     = gatherPhones($row);
          $services   = gatherServices($row);
        ?>
        <li class="ent-cards-grid__item" role="listitem">
          <article class="ent-card" aria-labelledby="ent-card-title-<?= h((string)$entityId) ?>">
            <header class="ent-card-head">
              <div class="ent-card-icon" aria-hidden="true">🏦</div>
              <h2 id="ent-card-title-<?= h((string)$entityId) ?>" class="ent-card-title"><?= h($cardTitle) ?></h2>
              <span class="ent-badge" aria-label="Servicios activos">
                <?= h((string)count($services)) ?> servicios
              </span>
            </header>
            <div class="ent-card-body">
              <div class="ent-card-row">
                <span class="ent-card-label">Segmento</span>
                <span class="ent-card-value"><?= formatSegment($row) ?></span>
              </div>
              <div class="ent-card-row">
                <span class="ent-card-label">Provincia – Cantón</span>
                <span class="ent-card-value"><?= formatLocation($row) ?></span>
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
                  <?php
                    $emails = [];
                    if (!empty($row['emails']) && is_array($row['emails'])) {
                        foreach ($row['emails'] as $mailValue) {
                            if (!is_scalar($mailValue)) {
                                continue;
                            }
                            $mailTrim = trim((string)$mailValue);
                            if ($mailTrim === '') {
                                continue;
                            }
                            $emails[] = $mailTrim;
                        }
                    }
                    $mail = $emails[0] ?? trim((string)($row['email'] ?? ''));
                  ?>
                  <?= $mail === '' ? 'No especificado' : h($mail) ?>
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
            </div>
            <footer class="ent-card-actions">
              <button type="button"
                      class="btn btn-outline js-entidad-view"
                      data-entidad-id="<?= h((string)$entityId) ?>"
                      aria-haspopup="dialog"
                      aria-controls="ent-card-modal"
                      aria-label="Ver detalles de <?= h($cardTitle) ?>">
                Ver
              </button>
              <a class="btn btn-primary" href="/comercial/entidades/editar?id=<?= h((string)$entityId) ?>">Editar</a>
              <form method="post" action="/comercial/entidades/eliminar" class="ent-card-delete" aria-label="Eliminar <?= h($cardTitle) ?>">
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
        <a href="<?= h(buildPageUrl($prev, $filters, $perPage)) ?>" rel="prev">&laquo; Anterior</a>
      <?php else: ?>
        <span class="disabled" aria-disabled="true">&laquo; Anterior</span>
      <?php endif; ?>

      <span aria-live="polite">Página <?= h((string)(int)$page) ?> de <?= h((string)(int)$pages) ?></span>

      <?php if ($page < $pages): ?>
        <a href="<?= h(buildPageUrl($next, $filters, $perPage)) ?>" rel="next">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="disabled" aria-disabled="true">Siguiente &raquo;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<div id="ent-card-modal" class="ent-modal" data-modal aria-hidden="true">
  <div class="ent-modal__overlay" data-close-modal tabindex="-1" aria-hidden="true"></div>
  <div class="ent-modal__box"
       role="dialog"
       aria-modal="true"
       aria-labelledby="ent-card-modal-title"
       aria-describedby="ent-card-modal-subtitle ent-card-modal-error"
       tabindex="-1">
    <div tabindex="0" data-modal-sentinel="start"></div>
    <button type="button" class="ent-modal__close" aria-label="Cerrar" data-close-modal>&times;</button>
    <div class="ent-modal__header">
      <div class="ent-card-icon" aria-hidden="true">🏦</div>
      <div>
        <h2 id="ent-card-modal-title" class="ent-card-title">Entidad</h2>
        <p id="ent-card-modal-subtitle" class="ent-card-subtitle">—</p>
      </div>
      <span id="ent-card-modal-servicios" class="ent-badge" aria-live="polite">0 servicios</span>
    </div>
    <div class="ent-modal__body">
      <div id="ent-card-modal-error" class="ent-modal__error" role="alert" aria-live="assertive"></div>
      <dl class="ent-details">
        <div><dt>Ubicación</dt><dd id="ent-md-ubicacion">—</dd></div>
        <div><dt>Segmento</dt><dd id="ent-md-segmento">—</dd></div>
        <div><dt>Tipo</dt><dd id="ent-md-tipo">—</dd></div>
        <div><dt>RUC</dt><dd id="ent-md-ruc">—</dd></div>
        <div><dt>Teléfono fijo</dt><dd id="ent-md-tfijo">—</dd></div>
        <div><dt>Teléfono móvil</dt><dd id="ent-md-tmovil">—</dd></div>
        <div><dt>Correo</dt><dd id="ent-md-email">—</dd></div>
        <div><dt>Notas</dt><dd id="ent-md-notas">—</dd></div>
        <div><dt>Servicios activos</dt><dd id="ent-md-servicios">—</dd></div>
      </dl>
    </div>
    <div class="ent-modal__footer">
      <button type="button" class="btn btn-outline" data-close-modal data-modal-initial-focus>Cerrar</button>
    </div>
    <div tabindex="0" data-modal-sentinel="end"></div>
  </div>
</div>

<script src="/js/entidades_cards.js" defer></script>
