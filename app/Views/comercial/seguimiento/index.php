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

$fechaFiltro  = isset($filters['fecha']) ? (string)$filters['fecha'] : '';
$desdeFiltro  = isset($filters['desde']) ? (string)$filters['desde'] : '';
$hastaFiltro  = isset($filters['hasta']) ? (string)$filters['hasta'] : '';
$coopFiltro   = isset($filters['coop']) ? (string)$filters['coop'] : '';
$tipoFiltro   = isset($filters['tipo']) ? (string)$filters['tipo'] : '';
$qFiltro      = isset($filters['q']) ? (string)$filters['q'] : '';
$ticketFiltro = isset($filters['ticket']) ? (string)$filters['ticket'] : '';
$advancedOpen = $hastaFiltro !== '' || $ticketFiltro !== '' || $fechaFiltro !== '';

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
      <input type="hidden" name="fecha" value="<?= seguimiento_h($fechaFiltro) ?>">
      <div class="seguimiento-filters__basic">
        <div class="seguimiento-filters__field seguimiento-filters__field--wide">
          <label for="seguimiento-q">Buscar descripción</label>
          <input id="seguimiento-q" type="text" name="q" value="<?= seguimiento_h($qFiltro) ?>" placeholder="Buscar en las notas">
        </div>
        <div class="seguimiento-filters__field">
          <label for="seguimiento-desde">Fecha de inicio</label>
          <input id="seguimiento-desde" type="date" name="desde" value="<?= seguimiento_h($desdeFiltro) ?>">
        </div>
        <div class="seguimiento-filters__field">
          <label for="seguimiento-coop">Entidad</label>
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
      </div>

      <div class="seguimiento-filters__actions">
        <button
          type="button"
          class="btn btn-ghost"
          data-action="seguimiento-toggle-filtros"
          aria-expanded="<?= $advancedOpen ? 'true' : 'false' ?>"
          aria-controls="seguimiento-filtros-avanzados"
        >
          <span class="material-symbols-outlined" aria-hidden="true">tune</span>
          <span data-label-open<?= $advancedOpen ? ' hidden' : '' ?>>Más filtros</span>
          <span data-label-close<?= $advancedOpen ? '' : ' hidden' ?>>Menos filtros</span>
        </button>
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

      <div
        class="seguimiento-filters__advanced"
        id="seguimiento-filtros-avanzados"
        data-seguimiento-filters-advanced
        <?= $advancedOpen ? '' : 'hidden' ?>
      >
        <div class="seguimiento-filters__field">
          <label for="seguimiento-hasta">Fecha de finalización</label>
          <input id="seguimiento-hasta" type="date" name="hasta" value="<?= seguimiento_h($hastaFiltro) ?>">
        </div>
        <div class="seguimiento-filters__field seguimiento-filters__field--wide">
          <label for="seguimiento-ticket">Ticket o descripción</label>
          <input
            id="seguimiento-ticket"
            type="text"
            name="ticket"
            value="<?= seguimiento_h($ticketFiltro) ?>"
            placeholder="Ej. INC-2025-00001"
            autocomplete="off"
            list="seguimiento-ticket-opciones"
            data-ticket-filter
          >
          <datalist id="seguimiento-ticket-opciones"></datalist>
          <p class="seguimiento-filters__hint">Escribe al menos 3 caracteres para ver sugerencias por código o descripción.</p>
        </div>
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
            $fechaInicio = isset($item['fecha_inicio']) ? (string)$item['fecha_inicio'] : '';
            $fechaFin    = isset($item['fecha_fin']) ? (string)$item['fecha_fin'] : '';

            $fechaInicioTexto = '';
            if ($fechaInicio !== '' && ($tsInicio = strtotime($fechaInicio)) !== false) {
                $fechaInicioTexto = date('d/m/Y', $tsInicio);
            }

            $fechaFinTexto = '';
            if ($fechaFin !== '' && ($tsFin = strtotime($fechaFin)) !== false) {
                $fechaFinTexto = date('d/m/Y', $tsFin);
            }

            $payload = [
                'id'                  => isset($item['id']) ? (int)$item['id'] : 0,
                'id_cooperativa'      => isset($item['id_cooperativa']) ? (int)$item['id_cooperativa'] : 0,
                'entidad'             => isset($item['cooperativa']) ? (string)$item['cooperativa'] : '',
                'cooperativa'         => isset($item['cooperativa']) ? (string)$item['cooperativa'] : '',
                'fecha_inicio'        => $fechaInicio,
                'fecha_inicio_texto'  => $fechaInicioTexto,
                'fecha_fin'           => $fechaFin,
                'fecha_fin_texto'     => $fechaFinTexto,
                'tipo'                => isset($item['tipo']) ? (string)$item['tipo'] : '',
                'descripcion'         => isset($item['descripcion']) ? (string)$item['descripcion'] : '',
                'contacto_id'         => isset($item['id_contacto']) ? (int)$item['id_contacto'] : null,
                'contacto_nombre'     => isset($item['contacto_nombre']) ? (string)$item['contacto_nombre'] : '',
                'contacto_telefono'   => isset($item['contacto_telefono']) ? (string)$item['contacto_telefono'] : '',
                'contacto_email'      => isset($item['contacto_email']) ? (string)$item['contacto_email'] : '',
                'ticket_id'           => isset($item['ticket_id']) ? (int)$item['ticket_id'] : null,
                'ticket_codigo'       => isset($item['ticket_codigo']) ? (string)$item['ticket_codigo'] : '',
                'ticket_departamento' => isset($item['ticket_departamento']) ? (string)$item['ticket_departamento'] : '',
                'ticket_tipo'         => isset($item['ticket_tipo']) ? (string)$item['ticket_tipo'] : '',
                'ticket_prioridad'    => isset($item['ticket_prioridad']) ? (string)$item['ticket_prioridad'] : '',
                'ticket_estado'       => isset($item['ticket_estado']) ? (string)$item['ticket_estado'] : '',
                'datos_reunion'       => isset($item['datos_reunion']) ? $item['datos_reunion'] : null,
                'datos_ticket'        => isset($item['datos_ticket']) ? $item['datos_ticket'] : null,
                'usuario'             => isset($item['usuario']) ? (string)$item['usuario'] : '',
                'creado_en'           => isset($item['creado_en']) ? (string)$item['creado_en'] : '',
                'editado_en'          => isset($item['editado_en']) ? (string)$item['editado_en'] : '',
            ];

            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($jsonPayload)) {
                $jsonPayload = '{}';
            }
          ?>
          <article
            class="seguimiento-card"
            role="button"
            tabindex="0"
            data-seguimiento-card
            data-item='<?= seguimiento_h($jsonPayload) ?>'
            aria-haspopup="dialog"
            aria-label="Ver seguimiento de <?= seguimiento_h($item['cooperativa'] ?? '') ?>"
            title="Ver seguimiento de <?= seguimiento_h($item['cooperativa'] ?? '') ?>"
          >
            <span class="seguimiento-card__accent" aria-hidden="true"></span>
            <header class="seguimiento-card__header">
              <h2 class="seguimiento-card__title"><?= seguimiento_h($item['cooperativa'] ?? '') ?></h2>
              <?php if (!empty($item['tipo'])): ?>
                <span class="seguimiento-card__badge"><?= seguimiento_h($item['tipo']) ?></span>
              <?php endif; ?>
            </header>
            <p class="seguimiento-card__desc"><?= seguimiento_h($item['descripcion'] ?? '') ?></p>
            <dl class="seguimiento-card__meta seguimiento-card__meta--grid">
              <div>
                <dt>Fecha inicio</dt>
                <dd>
                  <?php $inicioVacio = $fechaInicioTexto === ''; ?>
                  <span class="seguimiento-card__value<?= $inicioVacio ? ' seguimiento-card__value--empty' : '' ?>" data-field="inicio">
                    <?= seguimiento_h($inicioVacio ? '' : $fechaInicioTexto) ?>
                  </span>
                </dd>
              </div>
              <div>
                <dt>Fecha finalización</dt>
                <dd>
                  <?php $finVacio = $fechaFinTexto === ''; ?>
                  <span class="seguimiento-card__value<?= $finVacio ? ' seguimiento-card__value--empty' : '' ?>" data-field="fin">
                    <?= seguimiento_h($finVacio ? '' : $fechaFinTexto) ?>
                  </span>
                </dd>
              </div>
              <div>
                <dt>Registrado por</dt>
                <dd>
                  <?php $usuarioTexto = isset($item['usuario']) ? (string)$item['usuario'] : ''; ?>
                  <span class="seguimiento-card__value<?= $usuarioTexto === '' ? ' seguimiento-card__value--empty' : '' ?>" data-field="usuario">
                    <?= seguimiento_h($usuarioTexto) ?>
                  </span>
                </dd>
              </div>
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
    </header>
    <form class="seguimiento-modal__form seguimiento-form" data-seguimiento-form>
      <input type="hidden" name="id" value="">
      <div class="seguimiento-form__row">
        <div class="seguimiento-form__field">
          <label for="modal-fecha-inicio">Fecha de inicio</label>
          <input id="modal-fecha-inicio" type="date" name="fecha_inicio" required>
        </div>
        <div class="seguimiento-form__field">
          <label for="modal-fecha-fin">Fecha de finalización</label>
          <input id="modal-fecha-fin" type="date" name="fecha_fin">
        </div>
      </div>

      <div class="seguimiento-form__field seguimiento-form__field--wide">
        <label for="modal-entidad">Entidad</label>
        <select id="modal-entidad" name="id_cooperativa" required>
          <option value="">Seleccione</option>
          <?php foreach ($cooperativas as $coop): ?>
            <?php $value = isset($coop['id']) ? (string)$coop['id'] : ''; ?>
            <option value="<?= seguimiento_h($value) ?>"><?= seguimiento_h($coop['nombre'] ?? '') ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="seguimiento-form__field">
        <label for="modal-tipo">Tipo de gestión</label>
        <select id="modal-tipo" name="tipo" required>
          <option value="">Seleccione</option>
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

      <section class="seguimiento-form__section" data-seguimiento-section="contacto" hidden>
        <h3>Contacto relacionado</h3>
        <div class="seguimiento-form__field">
          <label for="modal-contacto">Seleccionar contacto</label>
          <select id="modal-contacto" name="id_contacto">
            <option value="">Seleccione</option>
          </select>
        </div>
        <div class="seguimiento-contacto-resumen" data-contacto-resumen>
          <div>
            <span>Nombre</span>
            <p data-contacto-dato="nombre"></p>
          </div>
          <div>
            <span>Celular</span>
            <p data-contacto-dato="telefono"></p>
          </div>
          <div>
            <span>Correo</span>
            <p data-contacto-dato="email"></p>
          </div>
        </div>
      </section>

      <section class="seguimiento-form__section" data-seguimiento-section="ticket" hidden>
        <h3>Ticket relacionado</h3>
        <div class="seguimiento-form__field">
          <label for="modal-ticket-buscar">Buscar ticket</label>
          <input id="modal-ticket-buscar" type="text" name="ticket_buscar" placeholder="Ej. INC-2025-00001" autocomplete="off">
          <datalist id="modal-ticket-opciones"></datalist>
          <input type="hidden" name="ticket_id" id="modal-ticket-id" value="">
          <input type="hidden" name="ticket_datos" id="modal-ticket-datos" value="">
        </div>
        <div class="seguimiento-ticket-resumen" data-ticket-resumen>
          <div>
            <span>Código</span>
            <p data-ticket-dato="codigo"></p>
          </div>
          <div>
            <span>Departamento</span>
            <p data-ticket-dato="departamento"></p>
          </div>
          <div>
            <span>Tipo incidencia</span>
            <p data-ticket-dato="tipo"></p>
          </div>
          <div>
            <span>Prioridad</span>
            <p data-ticket-dato="prioridad"></p>
          </div>
          <div>
            <span>Estado</span>
            <p data-ticket-dato="estado"></p>
          </div>
        </div>
      </section>

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
      </div>
    </form>
  </div>
</div>
<script src="/js/seguimiento.js" defer></script>
