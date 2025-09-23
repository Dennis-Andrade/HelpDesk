<?php
/** @var array $items */
/** @var array $crumbs */
/** @var string $csrf */
/** @var string $q */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */

$crumbs = $crumbs ?? [];
$items  = is_array($items ?? null) ? $items : [];
$q      = (string)($q ?? '');
$total  = (int)($total ?? count($items));
$page   = (int)($page ?? 1);
$perPage = (int)($perPage ?? 20);
$csrf   = (string)($csrf ?? '');

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function entSegmento(array $row): string
{
    $label = trim((string)($row['segmento'] ?? $row['segmento_nombre'] ?? ''));
    if ($label !== '') {
        return $label;
    }
    $id = $row['id_segmento'] ?? null;
    if ($id === null || $id === '') {
        return 'No especificado';
    }
    return 'Segmento ' . (int)$id;
}

function entUbicacion(array $row): string
{
    $prov = trim((string)($row['provincia'] ?? $row['provincia_nombre'] ?? ''));
    $canton = trim((string)($row['canton'] ?? $row['canton_nombre'] ?? ''));
    if ($prov === '' && $canton === '') {
        return 'No especificado';
    }
    if ($prov === '') {
        return $canton;
    }
    if ($canton === '') {
        return $prov;
    }
    return $prov . ' - ' . $canton;
}

function entTelefono(?string $value): string
{
    $trim = trim((string)$value);
    return $trim === '' ? 'No especificado' : $trim;
}

function entServicios(array $row): array
{
    $raw = $row['servicios'] ?? $row['servicios_activos'] ?? $row['servicios_nombres'] ?? [];
    if (is_string($raw)) {
        $parts = array_map('trim', preg_split('/[,;]\s*/', $raw));
    } elseif (is_array($raw)) {
        $parts = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $label = $item['nombre_servicio'] ?? $item['nombre'] ?? reset($item);
            } else {
                $label = $item;
            }
            $label = trim((string)$label);
            if ($label !== '') {
                $parts[] = $label;
            }
        }
    } else {
        $parts = [];
    }
    $parts = array_values(array_filter($parts, static fn($s) => $s !== ''));
    return $parts;
}

include __DIR__ . '/../../partials/breadcrumbs.php';
?>
<link rel="stylesheet" href="/css/comercial_style/entidades-cards.css">
<section class="ent-cards-wrapper" aria-labelledby="entidades-heading">
  <header class="ent-cards-header">
    <div class="ent-cards-header__titles">
      <h1 id="entidades-heading" class="ent-title">Entidades financieras</h1>
      <p class="ent-cards-header__summary" aria-live="polite">
        <?= (int)$total ?> entidades · Página <?= (int)$page ?> de <?= max(1, (int)ceil(max(1, $total) / max(1, $perPage))) ?>
      </p>
    </div>
    <div class="ent-cards-header__actions">
      <a class="btn btn-primary" href="/comercial/entidades/crear">Nueva entidad</a>
      <form class="ent-cards-search" action="/comercial/entidades" method="get" role="search" aria-label="Buscar entidades">
        <label for="ent-search" class="ent-cards-search__label">Buscar por nombre o RUC</label>
        <div class="ent-cards-search__group">
          <input
            id="ent-search"
            name="q"
            type="search"
            value="<?= h($q) ?>"
            placeholder="Ej. Cooperativa"
            aria-describedby="ent-search-help"
          >
          <button class="btn btn-outline" type="submit">Buscar</button>
        </div>
        <span id="ent-search-help" class="ent-cards-search__help">Presiona enter para filtrar resultados</span>
      </form>
    </div>
  </header>

  <?php if (empty($items)) : ?>
    <div class="ent-cards-empty" role="status">No se encontraron entidades con los criterios actuales.</div>
  <?php else : ?>
    <div class="ent-cards-grid" role="list">
      <?php foreach ($items as $index => $row): ?>
        <?php
          $id = (int)($row['id'] ?? $row['id_entidad'] ?? $row['id_cooperativa'] ?? 0);
          $nombre = $row['nombre'] ?? '';
          $segmento = entSegmento((array)$row);
          $ubicacion = entUbicacion((array)$row);
          $telefonoFijo = entTelefono($row['telefono_fijo_1'] ?? $row['telefono_fijo'] ?? $row['telefono'] ?? null);
          $telefonoMovil = entTelefono($row['telefono_movil'] ?? null);
          $email = entTelefono($row['email'] ?? null);
          $servicios = entServicios((array)$row);
        ?>
        <article class="ent-card-item" role="listitem">
          <header class="ent-card-item__header">
            <div class="ent-card-item__icon" aria-hidden="true">🏦</div>
            <div class="ent-card-item__text">
              <h2 class="ent-card-item__title"><?= h($nombre) ?></h2>
              <p class="ent-card-item__subtitle"><?= h($segmento) ?></p>
            </div>
          </header>
          <div class="ent-card-item__body">
            <dl class="ent-card-item__details">
              <div>
                <dt>Ubicación</dt>
                <dd><?= h($ubicacion) ?></dd>
              </div>
              <div>
                <dt>Teléfono fijo</dt>
                <dd><?= h($telefonoFijo) ?></dd>
              </div>
              <div>
                <dt>Teléfono móvil</dt>
                <dd><?= h($telefonoMovil) ?></dd>
              </div>
              <div>
                <dt>Email</dt>
                <dd><?= h($email) ?></dd>
              </div>
            </dl>
            <div class="ent-card-item__services" aria-label="Servicios activos">
              <?php if (!empty($servicios)): ?>
                <?php foreach ($servicios as $serviceName): ?>
                  <span class="ent-card-item__badge"><?= h($serviceName) ?></span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="ent-card-item__badge ent-card-item__badge--empty">Sin servicios registrados</span>
              <?php endif; ?>
            </div>
          </div>
          <footer class="ent-card-item__footer">
            <button
              type="button"
              class="btn btn-outline ent-card-item__action"
              data-entity-id="<?= $id ?>"
              aria-haspopup="dialog"
              aria-controls="ent-card-modal"
            >Ver</button>
            <a class="btn btn-primary ent-card-item__action" href="/comercial/entidades/editar?id=<?= $id ?>">Editar</a>
            <form
              class="ent-card-item__delete"
              method="post"
              action="/comercial/entidades/eliminar"
              onsubmit="return confirm('¿Deseas eliminar esta entidad?');"
            >
              <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">
              <input id="ent-delete-<?= $index ?>" type="hidden" name="id" value="<?= $id ?>">
              <button class="btn btn-danger ent-card-item__action" type="submit" aria-labelledby="delete-label-<?= $index ?>">
                <span id="delete-label-<?= $index ?>" class="visually-hidden">Eliminar <?= h($nombre) ?></span>
                <span aria-hidden="true">Eliminar</span>
              </button>
            </form>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<div id="ent-card-modal" class="ent-card-modal" aria-hidden="true">
  <div class="ent-card-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ent-card-modal-title">
    <button type="button" class="ent-card-modal__close" aria-label="Cerrar modal">×</button>
    <header class="ent-card-modal__header">
      <div class="ent-card-item__icon" aria-hidden="true">🏦</div>
      <div class="ent-card-modal__titles">
        <h2 id="ent-card-modal-title" class="ent-card-item__title">Entidad</h2>
        <p id="ent-card-modal-segmento" class="ent-card-item__subtitle">Segmento</p>
      </div>
      <span id="ent-card-modal-serv-count" class="ent-card-item__badge">0 servicios</span>
    </header>
    <div class="ent-card-modal__body">
      <dl class="ent-card-modal__details">
        <div><dt>Ubicación</dt><dd id="modal-ubicacion">—</dd></div>
        <div><dt>Tipo</dt><dd id="modal-tipo">—</dd></div>
        <div><dt>RUC</dt><dd id="modal-ruc">—</dd></div>
        <div><dt>Teléfono fijo</dt><dd id="modal-telefono-fijo">—</dd></div>
        <div><dt>Teléfono móvil</dt><dd id="modal-telefono-movil">—</dd></div>
        <div><dt>Email</dt><dd id="modal-email">—</dd></div>
        <div><dt>Notas</dt><dd id="modal-notas">—</dd></div>
        <div><dt>Servicios</dt><dd id="modal-servicios">—</dd></div>
      </dl>
    </div>
    <footer class="ent-card-modal__footer">
      <button type="button" class="btn btn-outline ent-card-modal__close">Cerrar</button>
    </footer>
  </div>
</div>
<script src="/js/entidades_cards.js" defer></script>
