<?php
use App\Services\Shared\Pagination;

if (!function_exists('seguimiento_h')) {
    function seguimiento_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$items        = isset($items) && is_array($items) ? $items : [];
$filters      = isset($filters) && is_array($filters) ? $filters : [];
$cooperativas = isset($cooperativas) && is_array($cooperativas) ? $cooperativas : [];
$tipos        = isset($tipos) && is_array($tipos) ? $tipos : [];

$page    = isset($page) ? (int)$page : 1;
$perPage = isset($perPage) ? (int)$perPage : 10;
$total   = isset($total) ? (int)$total : 0;

$pagination = Pagination::fromRequest([
    'page'    => $page,
    'perPage' => $perPage,
], 1, max(1, $perPage), $total);

$page  = $pagination->page;
$perPage = $pagination->perPage;
$pages = $pagination->pages();
$prev  = max(1, $page - 1);
$next  = min($pages, $page + 1);

$fechaFiltro = isset($filters['fecha']) ? (string)$filters['fecha'] : '';
$desdeFiltro = isset($filters['desde']) ? (string)$filters['desde'] : '';
$hastaFiltro = isset($filters['hasta']) ? (string)$filters['hasta'] : '';
$coopFiltro  = isset($filters['coop']) ? (string)$filters['coop'] : '';
$tipoFiltro  = isset($filters['tipo']) ? (string)$filters['tipo'] : '';
$qFiltro     = isset($filters['q']) ? (string)$filters['q'] : '';
$ticketFiltro = isset($filters['ticket']) ? (string)$filters['ticket'] : '';

$fechaForm = $fechaFiltro !== '' ? $fechaFiltro : date('Y-m-d');

function buildSeguimientoPageUrl(int $pageNumber, array $filters, int $perPage): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return '/comercial/eventos' . ($queryString !== '' ? '?' . $queryString : '');
}
?>
<section class="ent-list ent-seguimiento" aria-labelledby="seguimiento-title">
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="seguimiento-title" class="ent-title">Seguimiento diario</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= seguimiento_h((string)$total) ?> registros · Página <?= seguimiento_h((string)$page) ?> de <?= seguimiento_h((string)max(1, $pages)) ?>
      </p>
    </div>
    <div class="ent-toolbar__actions">
      <form class="seguimiento-export" method="get" action="/comercial/eventos/exportar">
        <input type="hidden" name="fecha" value="<?= seguimiento_h($fechaFiltro) ?>">
        <input type="hidden" name="desde" value="<?= seguimiento_h($desdeFiltro) ?>">
        <input type="hidden" name="hasta" value="<?= seguimiento_h($hastaFiltro) ?>">
        <input type="hidden" name="coop" value="<?= seguimiento_h($coopFiltro) ?>">
        <input type="hidden" name="tipo" value="<?= seguimiento_h($tipoFiltro) ?>">
        <input type="hidden" name="q" value="<?= seguimiento_h($qFiltro) ?>">
        <input type="hidden" name="ticket" value="<?= seguimiento_h($ticketFiltro) ?>">
        <button type="submit" class="btn btn-outline">
          <span class="material-symbols-outlined" aria-hidden="true">download</span>
          Descargar Excel
        </button>
      </form>
    </div>
  </header>

  <section class="seguimiento-card seguimiento-card--filters">
    <form class="seguimiento-filters" method="get" action="/comercial/eventos" role="search">
      <div class="seguimiento-filters__field">
        <label for="seguimiento-fecha">Fecha</label>
        <input id="seguimiento-fecha" type="date" name="fecha" value="<?= seguimiento_h($fechaFiltro) ?>" data-default="<?= seguimiento_h($fechaForm) ?>">
      </div>
      <div class="seguimiento-filters__field">
        <label for="seguimiento-desde">Desde</label>
        <input id="seguimiento-desde" type="date" name="desde" value="<?= seguimiento_h($desdeFiltro) ?>">
      </div>
      <div class="seguimiento-filters__field">
        <label for="seguimiento-hasta">Hasta</label>
        <input id="seguimiento-hasta" type="date" name="hasta" value="<?= seguimiento_h($hastaFiltro) ?>">
      </div>
      <div class="seguimiento-filters__field">
        <label for="seguimiento-coop">Cooperativa</label>
        <select id="seguimiento-coop" name="coop">
          <option value="">Todas</option>
          <?php foreach ($cooperativas as $coop): ?>
            <?php $value = isset($coop['id']) ? (string)$coop['id'] : ''; ?>
            <option value="<?= seguimiento_h($value) ?>" <?= $value === $coopFiltro ? 'selected' : '' ?>><?= seguimiento_h($coop['nombre'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="seguimiento-filters__field">
        <label for="seguimiento-tipo">Tipo</label>
        <select id="seguimiento-tipo" name="tipo">
          <option value="">Todos</option>
          <?php foreach ($tipos as $tipo): ?>
            <?php $tipoNombre = (string)$tipo; ?>
            <option value="<?= seguimiento_h($tipoNombre) ?>" <?= $tipoNombre === $tipoFiltro ? 'selected' : '' ?>><?= seguimiento_h($tipoNombre) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="seguimiento-filters__field">
        <label for="seguimiento-ticket">Ticket</label>
        <input id="seguimiento-ticket" type="text" name="ticket" value="<?= seguimiento_h($ticketFiltro) ?>" placeholder="Ej. 1250">
      </div>
      <div class="seguimiento-filters__field seguimiento-filters__field--wide">
        <label for="seguimiento-q">Descripción</label>
        <input id="seguimiento-q" type="text" name="q" value="<?= seguimiento_h($qFiltro) ?>" placeholder="Buscar en las notas">
      </div>
      <div class="seguimiento-filters__actions">
        <a class="btn btn-secondary" href="/comercial/eventos/crear">
          <span class="material-symbols-outlined" aria-hidden="true">add</span>
          Nuevo
        </a>
        <button type="submit" class="btn btn-primary">
          <span class="material-symbols-outlined" aria-hidden="true">search</span>
          Buscar
        </button>
        <button type="button" class="btn btn-outline" data-action="seguimiento-reset">
          <span class="material-symbols-outlined" aria-hidden="true">undo</span>
          Limpiar
        </button>
      </div>
    </form>
  </section>

  <div class="seguimiento-divider" aria-hidden="true"></div>

  <section class="seguimiento-results" aria-live="polite">
    <?php if (!$items): ?>
      <div class="seguimiento-empty">
        <span class="material-symbols-outlined" aria-hidden="true">inbox</span>
        <p>No se registraron actividades con los filtros seleccionados.</p>
      </div>
    <?php else: ?>
      <div class="seguimiento-cards">
        <?php foreach ($items as $item): ?>
          <?php
            $fecha = isset($item['fecha']) ? (string)$item['fecha'] : '';
            $fechaTexto = '';
            if ($fecha !== '') {
                $ts = strtotime($fecha);
                if ($ts !== false) {
                    $fechaTexto = date('d/m/Y', $ts);
                }
            }
            $descripcion = isset($item['descripcion']) ? (string)$item['descripcion'] : '';
            $ticket = isset($item['ticket']) ? trim((string)$item['ticket']) : '';
            $usuario = isset($item['usuario']) ? (string)$item['usuario'] : '';
            $creado = isset($item['creado_en']) ? (string)$item['creado_en'] : '';
            $contactNumber = isset($item['contact_number']) ? (int)$item['contact_number'] : 0;
            $contactDataRaw = $item['contact_data'] ?? null;
            $contactData = '';
            if (is_array($contactDataRaw)) {
                $pairs = [];
                foreach ($contactDataRaw as $key => $value) {
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $label = is_string($key) ? trim((string)$key) : '';
                    $textValue = is_scalar($value) ? trim((string)$value) : '';
                    if ($textValue === '') {
                        continue;
                    }
                    if ($label !== '') {
                        $pairs[] = $label . ': ' . $textValue;
                    } else {
                        $pairs[] = $textValue;
                    }
                }
                $contactData = implode('; ', $pairs);
            } elseif (is_string($contactDataRaw)) {
                $contactData = trim($contactDataRaw);
            }

            $payload = [
                'id'                 => isset($item['id']) ? (int)$item['id'] : 0,
                'cooperativa_id'     => isset($item['id_cooperativa']) ? (int)$item['id_cooperativa'] : 0,
                'cooperativa'        => isset($item['cooperativa']) ? (string)$item['cooperativa'] : '',
                'fecha'              => $fecha !== '' ? $fecha : '',
                'fecha_texto'        => $fechaTexto !== '' ? $fechaTexto : $fecha,
                'tipo'               => isset($item['tipo']) ? (string)$item['tipo'] : '',
                'descripcion'        => $descripcion,
                'ticket'             => $ticket,
                'usuario'            => $usuario,
                'creado_en'          => $creado,
                'contact_number'     => $contactNumber > 0 ? $contactNumber : null,
                'contact_data'       => $contactDataRaw,
                'contact_data_text'  => $contactData,
            ];

            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($jsonPayload === false) {
                $jsonPayload = '{}';
            }
          ?>
          <article
            class="seguimiento-card"
            role="button"
            tabindex="0"
            data-seguimiento-card
            data-item="<?= seguimiento_h($jsonPayload) ?>"
            aria-haspopup="dialog"
            aria-label="Ver seguimiento de <?= seguimiento_h($item['cooperativa'] ?? '') ?>"
            title="Ver seguimiento de <?= seguimiento_h($item['cooperativa'] ?? '') ?>"
          >
            <span class="seguimiento-card__accent" aria-hidden="true"></span>
            <header class="seguimiento-card__header">
              <div>
                <p class="seguimiento-card__date"><?= seguimiento_h($fechaTexto ?: $fecha) ?></p>
                <h2 class="seguimiento-card__title"><?= seguimiento_h($item['cooperativa'] ?? '') ?></h2>
              </div>
              <?php if (!empty($item['tipo'])): ?>
                <span class="seguimiento-card__badge"><?= seguimiento_h($item['tipo']) ?></span>
              <?php endif; ?>
            </header>
            <p class="seguimiento-card__desc"><?= seguimiento_h($descripcion) ?></p>
            <dl class="seguimiento-card__meta">
              <?php if ($ticket !== ''): ?>
                <div>
                  <dt>Ticket</dt>
                  <dd><?= seguimiento_h($ticket) ?></dd>
                </div>
              <?php endif; ?>
              <?php if ($usuario !== ''): ?>
                <div>
                  <dt>Registrado por</dt>
                  <dd><?= seguimiento_h($usuario) ?></dd>
                </div>
              <?php endif; ?>
              <?php if ($creado !== ''): ?>
                <div>
                  <dt>Creado</dt>
                  <dd><?= seguimiento_h($creado) ?></dd>
                </div>
              <?php endif; ?>
              <?php if ($contactNumber > 0): ?>
                <div>
                  <dt>No. contacto</dt>
                  <dd><?= seguimiento_h((string)$contactNumber) ?></dd>
                </div>
              <?php endif; ?>
              <?php if ($contactData !== ''): ?>
                <div>
                  <dt>Detalle de contacto</dt>
                  <dd><?= seguimiento_h($contactData) ?></dd>
                </div>
              <?php endif; ?>
            </dl>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <?php if ($pages > 1): ?>
    <nav class="ent-pagination" aria-label="Paginación de seguimiento">
      <a class="ent-pagination__link" href="<?= seguimiento_h(buildSeguimientoPageUrl($prev, $filters, $perPage)) ?>" aria-label="Página anterior"<?= $page <= 1 ? ' aria-disabled="true"' : '' ?>>
        <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
      </a>
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a class="ent-pagination__link<?= $p === $page ? ' ent-pagination__link--current' : '' ?>" href="<?= seguimiento_h(buildSeguimientoPageUrl($p, $filters, $perPage)) ?>">
          <?= seguimiento_h((string)$p) ?>
        </a>
      <?php endfor; ?>
      <a class="ent-pagination__link" href="<?= seguimiento_h(buildSeguimientoPageUrl($next, $filters, $perPage)) ?>" aria-label="Página siguiente"<?= $page >= $pages ? ' aria-disabled="true"' : '' ?>>
        <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
      </a>
    </nav>
  <?php endif; ?>
</section>

<div class="seguimiento-modal" data-seguimiento-modal hidden>
  <div class="seguimiento-modal__overlay" data-seguimiento-overlay></div>
  <div class="seguimiento-modal__dialog" data-seguimiento-dialog role="dialog" aria-modal="true" aria-labelledby="seguimiento-modal-title">
    <button type="button" class="seguimiento-modal__close" data-seguimiento-close aria-label="Cerrar detalle de seguimiento">
      <span class="material-symbols-outlined" aria-hidden="true">close</span>
    </button>
    <header class="seguimiento-modal__header">
      <h2 id="seguimiento-modal-title" data-seguimiento-modal-title>Detalle de seguimiento</h2>
      <p class="seguimiento-modal__subtitle" data-seguimiento-modal-subtitle></p>
    </header>
    <form class="seguimiento-modal__form seguimiento-form" data-seguimiento-form>
      <input type="hidden" name="id" value="">
      <div class="seguimiento-form__field">
        <label for="modal-fecha">Fecha de actividad</label>
        <input id="modal-fecha" type="date" name="fecha" required>
      </div>

      <div class="seguimiento-form__field">
        <label for="modal-coop">Cooperativa</label>
        <select id="modal-coop" name="id_cooperativa" required>
          <option value="">Seleccione</option>
          <?php foreach ($cooperativas as $coop): ?>
            <?php $value = isset($coop['id']) ? (string)$coop['id'] : ''; ?>
            <option value="<?= seguimiento_h($value) ?>"><?= seguimiento_h($coop['nombre'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="seguimiento-form__field">
        <label for="modal-tipo">Tipo de gestión</label>
        <select id="modal-tipo" name="tipo">
          <option value="">Seguimiento</option>
          <?php foreach ($tipos as $tipo): ?>
            <?php $tipoNombre = (string)$tipo; ?>
            <option value="<?= seguimiento_h($tipoNombre) ?>"><?= seguimiento_h($tipoNombre) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="seguimiento-form__field seguimiento-form__field--wide">
        <label for="modal-descripcion">Descripción</label>
        <textarea id="modal-descripcion" name="descripcion" rows="4" maxlength="600" required></textarea>
      </div>

      <div class="seguimiento-form__field">
        <label for="modal-ticket">Ticket relacionado</label>
        <input id="modal-ticket" type="text" name="ticket" placeholder="Opcional">
      </div>

      <div class="seguimiento-form__field">
        <label for="modal-contacto">No. contacto</label>
        <input id="modal-contacto" type="text" name="numero_contacto" placeholder="Opcional">
      </div>

      <div class="seguimiento-form__field seguimiento-form__field--wide">
        <label for="modal-contacto-detalle">Detalle de contacto</label>
        <textarea id="modal-contacto-detalle" name="datos_contacto" rows="3" placeholder="Información adicional"></textarea>
      </div>

      <div class="seguimiento-modal__meta" data-seguimiento-modal-meta></div>

      <div class="seguimiento-modal__actions">
        <button type="button" class="btn btn-primary" data-seguimiento-edit>
          <span class="material-symbols-outlined" aria-hidden="true">edit</span>
          Editar
        </button>
        <button type="button" class="btn btn-danger" data-seguimiento-delete>
          <span class="material-symbols-outlined" aria-hidden="true">delete</span>
          Eliminar
        </button>
        <button type="button" class="btn btn-outline" data-seguimiento-cancel>
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
          Cerrar
        </button>
      </div>
    </form>
  </div>
</div>
<script src="/js/seguimiento.js" defer></script>
