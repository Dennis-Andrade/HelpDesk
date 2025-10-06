<?php
use App\Services\Shared\Pagination;

if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$items        = isset($items) && is_array($items) ? $items : [];
$filters      = isset($filters) && is_array($filters) ? $filters : [];
$cooperativas = isset($cooperativas) && is_array($cooperativas) ? $cooperativas : [];
$tipos        = isset($tipos) && is_array($tipos) ? $tipos : [];
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

$estadoFiltro = isset($filters['estado']) ? (string)$filters['estado'] : '';
$coopFiltro   = isset($filters['coop']) ? (string)$filters['coop'] : '';
$ticketFiltro = isset($filters['ticket']) ? (string)$filters['ticket'] : '';

function buildPageUrlIncidencias(int $pageNumber, array $filters, int $perPage): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return '/comercial/incidencias' . ($queryString !== '' ? '?' . $queryString : '');
}
?>
<section class="ent-list ent-incidencias" aria-labelledby="incidencias-title">
  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="incidencias-title" class="ent-title">Incidencias para sistemas</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)$total) ?> incidencias · Página <?= h((string)$page) ?> de <?= h((string)$pages) ?>
      </p>
    </div>
  </header>

  <div class="incidencias-layout incidencias-layout--collapsed" data-incidencia-layout>
    <section class="card incidencias-card incidencias-card--form" aria-labelledby="incidencia-form-title" id="incidencia-form-card" data-incidencia-form hidden>
      <header class="incidencias-card__header">
        <h2 id="incidencia-form-title"><span class="material-symbols-outlined" aria-hidden="true">add_circle</span> Nueva incidencia</h2>
      </header>
      <form class="incidencias-form" method="post" action="/comercial/incidencias" autocomplete="off">
        <div class="incidencias-form__field">
          <label for="incidencia-cooperativa">Cooperativa</label>
          <select id="incidencia-cooperativa" name="id_cooperativa" required>
            <option value="">Seleccione</option>
            <?php foreach ($cooperativas as $coop): ?>
              <option value="<?= h((string)($coop['id'] ?? '')) ?>"><?= h($coop['nombre'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-form__field">
          <label for="incidencia-asunto">Asunto</label>
          <input id="incidencia-asunto" type="text" name="asunto" required maxlength="180" placeholder="Resumen de la incidencia">
        </div>
        <div class="incidencias-form__field">
          <label for="incidencia-tipo">Incidencia</label>
          <select id="incidencia-tipo" name="tipo_incidencia" required>
            <?php foreach ($tipos as $tipo): ?>
              <option value="<?= h($tipo) ?>"><?= h($tipo) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-form__field">
          <label for="incidencia-prioridad">Prioridad</label>
          <select id="incidencia-prioridad" name="prioridad" required>
            <?php foreach ($prioridades as $prioridad): ?>
              <option value="<?= h($prioridad) ?>"><?= h($prioridad) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-form__field incidencias-form__field--full">
          <label for="incidencia-descripcion">Descripción</label>
          <textarea id="incidencia-descripcion" name="descripcion" rows="3" placeholder="Detalles adicionales"></textarea>
        </div>
        <div class="incidencias-form__actions">
          <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" aria-hidden="true">save</span>
            Guardar
          </button>
        </div>
      </form>
    </section>

    <section class="incidencias-card incidencias-card--list">
      <form class="incidencias-filters" method="get" action="/comercial/incidencias" role="search">
        <div class="incidencias-filters__field">
          <label for="incidencias-ticket"># de ticket</label>
          <input id="incidencias-ticket" type="text" name="ticket" value="<?= h($ticketFiltro) ?>" placeholder="Ej. INC-2024-0001">
        </div>
        <div class="incidencias-filters__field">
          <label for="incidencias-estado">Estado</label>
          <select id="incidencias-estado" name="estado">
            <option value="">Todos</option>
            <?php foreach ($estados as $estadoItem): ?>
              <option value="<?= h($estadoItem) ?>" <?= $estadoItem === $estadoFiltro ? 'selected' : '' ?>><?= h($estadoItem) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-filters__field">
          <label for="incidencias-coop">Cooperativa</label>
          <select id="incidencias-coop" name="coop">
            <option value="">Todas</option>
            <?php foreach ($cooperativas as $coop): ?>
              <?php $value = isset($coop['id']) ? (string)$coop['id'] : ''; ?>
              <option value="<?= h($value) ?>" <?= $value === $coopFiltro ? 'selected' : '' ?>><?= h($coop['nombre'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-filters__actions">
          <button type="submit" class="btn btn-primary">
            <span class="material-symbols-outlined" aria-hidden="true">search</span>
            Buscar
          </button>
          <a class="btn btn-outline" href="/comercial/incidencias">
            <span class="material-symbols-outlined" aria-hidden="true">refresh</span>
            Limpiar
          </a>
          <button type="button"
                  class="btn btn-secondary"
                  data-incidencia-form-toggle
                  aria-controls="incidencia-form-card"
                  aria-expanded="false">
            <span class="material-symbols-outlined" aria-hidden="true">note_add</span>
            Nueva
          </button>
        </div>
      </form>

      <?php if (empty($items)): ?>
        <div class="card incidencias-empty" role="status">No hay incidencias registradas con los filtros seleccionados.</div>
      <?php else: ?>
        <div class="incidencias-table" role="table" aria-label="Listado de incidencias">
          <div class="incidencias-row incidencias-row--header" role="row">
            <span class="incidencias-cell incidencias-cell--fecha" role="columnheader">Fecha</span>
            <span class="incidencias-cell" role="columnheader">Cooperativa</span>
            <span class="incidencias-cell incidencias-cell--asunto" role="columnheader">Asunto</span>
            <span class="incidencias-cell" role="columnheader">Prioridad</span>
            <span class="incidencias-cell" role="columnheader">Estado</span>
            <span class="incidencias-cell" role="columnheader">Ticket</span>
            <span class="incidencias-cell incidencias-cell--acciones" role="columnheader">Acciones</span>
          </div>
          <?php foreach ($items as $row): ?>
            <?php
              $id            = isset($row['id']) ? (int)$row['id'] : 0;
              $fecha         = isset($row['creado_en']) ? (string)$row['creado_en'] : '';
              $fechaMostrar  = $fecha !== '' ? date('Y-m-d', strtotime($fecha)) : date('Y-m-d');
              $coopNombre    = (string)($row['cooperativa'] ?? '');
              $asunto        = (string)($row['asunto'] ?? '');
              $prioridad     = (string)($row['prioridad'] ?? '');
              $estadoActual  = (string)($row['estado'] ?? '');
              $ticketCodigo  = (string)($row['ticket_codigo'] ?? '');
              $ticketNumero  = (string)($row['ticket_numero'] ?? '');
              $ticketNumero  = $ticketNumero !== '' ? preg_replace('/[^0-9]/', '', $ticketNumero) : '';
              $ticketDisplay = $ticketCodigo !== ''
                  ? $ticketCodigo
                  : ($ticketNumero !== '' ? 'INC-' . str_pad($ticketNumero, 5, '0', STR_PAD_LEFT) : '—');
              $tipo          = (string)($row['tipo_incidencia'] ?? '');
              $descripcion   = (string)($row['descripcion'] ?? '');
              $contactoNombre= (string)($row['oficial_nombre'] ?? '');
              $contactoCorreo= (string)($row['oficial_correo'] ?? '');
              $contactoTel   = (string)($row['telefono_contacto'] ?? '');
              $contactoCargo = (string)($row['cargo'] ?? '');
              $contactoFecha = (string)($row['fecha_evento'] ?? '');
            ?>
            <div class="incidencias-row" role="row"
                 data-id="<?= h((string)$id) ?>"
                 data-fecha="<?= h($fechaMostrar) ?>"
                 data-cooperativa="<?= h($coopNombre) ?>"
                 data-asunto="<?= h($asunto) ?>"
                 data-prioridad="<?= h($prioridad) ?>"
                 data-estado="<?= h($estadoActual) ?>"
                 data-ticket="<?= h($ticketDisplay) ?>"
                 data-tipo="<?= h($tipo) ?>"
                 data-descripcion="<?= h($descripcion) ?>"
                 data-contacto-nombre="<?= h($contactoNombre) ?>"
                 data-contacto-correo="<?= h($contactoCorreo) ?>"
                 data-contacto-telefono="<?= h($contactoTel) ?>"
                 data-contacto-cargo="<?= h($contactoCargo) ?>"
                 data-contacto-fecha="<?= h($contactoFecha) ?>">
              <span class="incidencias-cell incidencias-cell--fecha" role="cell" data-label="Fecha"><?= h($fechaMostrar) ?></span>
              <span class="incidencias-cell" role="cell" data-label="Cooperativa"><?= h($coopNombre) ?></span>
              <span class="incidencias-cell incidencias-cell--asunto" role="cell" data-label="Asunto"><?= h($asunto) ?></span>
              <span class="incidencias-cell" role="cell" data-label="Prioridad"><?= h($prioridad) ?></span>
              <span class="incidencias-cell" role="cell" data-label="Estado">
                <span class="incidencias-badge incidencias-badge--<?= strtolower(str_replace(' ', '-', $estadoActual)) ?>"><?= h($estadoActual) ?></span>
              </span>
              <span class="incidencias-cell" role="cell" data-label="Ticket"><?= h($ticketDisplay) ?></span>
              <span class="incidencias-cell incidencias-cell--acciones" role="cell" data-label="Acciones">
                <button class="btn btn-outline" type="button" data-incidencia-open>
                  <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                  Ver
                </button>
                <form method="post" action="/comercial/incidencias/<?= h((string)$id) ?>/eliminar" class="incidencias-delete" onsubmit="return confirm('¿Deseas eliminar esta incidencia?');">
                  <button class="btn btn-danger" type="submit">
                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                    Eliminar
                  </button>
                </form>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
          <nav class="ent-pagination" aria-label="Paginación de incidencias">
            <a class="ent-pagination__link" href="<?= h(buildPageUrlIncidencias($prev, $filters, $perPage)) ?>" aria-label="Página anterior"<?= $page <= 1 ? ' aria-disabled="true"' : '' ?>>
              <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
            </a>
            <?php for ($p = 1; $p <= $pages; $p++): ?>
              <a class="ent-pagination__link<?= $p === $page ? ' ent-pagination__link--current' : '' ?>"
                 href="<?= h(buildPageUrlIncidencias($p, $filters, $perPage)) ?>"
                 aria-current="<?= $p === $page ? 'page' : 'false' ?>">
                <?= h((string)$p) ?>
              </a>
            <?php endfor; ?>
            <a class="ent-pagination__link" href="<?= h(buildPageUrlIncidencias($next, $filters, $perPage)) ?>" aria-label="Página siguiente"<?= $page >= $pages ? ' aria-disabled="true"' : '' ?>>
              <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
            </a>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
</section>

<div class="incidencias-modal" id="incidencias-modal" role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1">
  <div class="incidencias-modal__overlay" data-incidencia-close></div>
  <div class="incidencias-modal__card" role="document">
    <header class="incidencias-modal__header">
      <h2><span class="material-symbols-outlined" aria-hidden="true">visibility</span> Detalle de incidencia</h2>
      <button type="button" class="incidencias-modal__close" aria-label="Cerrar" data-incidencia-close>&times;</button>
    </header>
    <form class="incidencias-modal__form" id="incidencias-modal-form" method="post" action="">
      <div class="incidencias-modal__grid">
        <div class="incidencias-modal__field">
          <label for="modal-fecha">Fecha</label>
          <input id="modal-fecha" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-ticket">Ticket</label>
          <input id="modal-ticket" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-cooperativa">Cooperativa</label>
          <input id="modal-cooperativa" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-asunto">Asunto</label>
          <input id="modal-asunto" name="asunto" type="text" readonly required>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-tipo">Incidencia</label>
          <select id="modal-tipo" name="tipo_incidencia" disabled>
            <?php foreach ($tipos as $tipo): ?>
              <option value="<?= h($tipo) ?>"><?= h($tipo) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-prioridad">Prioridad</label>
          <select id="modal-prioridad" name="prioridad" disabled>
            <?php foreach ($prioridades as $prioridad): ?>
              <option value="<?= h($prioridad) ?>"><?= h($prioridad) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-estado">Estado</label>
          <select id="modal-estado" name="estado" disabled>
            <?php foreach ($estados as $estadoItem): ?>
              <option value="<?= h($estadoItem) ?>"><?= h($estadoItem) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field incidencias-modal__field--full">
          <label for="modal-descripcion">Descripción</label>
          <textarea id="modal-descripcion" name="descripcion" rows="4" readonly></textarea>
        </div>
      </div>
      <div class="incidencias-modal__actions">
        <button class="btn btn-outline" type="button" data-incidencia-edit>
          <span class="material-symbols-outlined" aria-hidden="true">edit</span>
          Editar
        </button>
        <button class="btn btn-primary" type="submit" data-incidencia-save hidden>
          <span class="material-symbols-outlined" aria-hidden="true">save</span>
          Guardar cambios
        </button>
        <button class="btn btn-danger" type="button" data-incidencia-delete>
          <span class="material-symbols-outlined" aria-hidden="true">delete</span>
          Eliminar
        </button>
      </div>
    </form>

    <section class="incidencias-modal__contacto" aria-labelledby="contacto-titulo">
      <h3 id="contacto-titulo">Contacto de la cooperativa</h3>
      <div class="incidencias-modal__grid incidencias-modal__grid--compact">
        <div class="incidencias-modal__field">
          <label for="modal-contacto-nombre">Nombre</label>
          <input id="modal-contacto-nombre" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-contacto-cargo">Cargo</label>
          <input id="modal-contacto-cargo" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-contacto-telefono">Teléfono</label>
          <input id="modal-contacto-telefono" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-contacto-correo">Correo</label>
          <input id="modal-contacto-correo" type="text" readonly>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-contacto-fecha">Última actualización</label>
          <input id="modal-contacto-fecha" type="text" readonly>
        </div>
      </div>
    </section>
  </div>
</div>

<form method="post" action="" id="incidencias-delete-form" style="display:none;"></form>

<script src="/js/incidencias.js" defer></script>
