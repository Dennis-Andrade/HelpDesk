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
$prioridades  = isset($prioridades) && is_array($prioridades) ? $prioridades : [];
$estados      = isset($estados) && is_array($estados) ? $estados : [];
$departamentos = isset($departamentos) && is_array($departamentos) ? $departamentos : [];
$tiposPorDepartamento = isset($tiposPorDepartamento) && is_array($tiposPorDepartamento) ? $tiposPorDepartamento : [];

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

$estadoFiltro        = isset($filters['estado']) ? (string)$filters['estado'] : '';
$coopFiltro          = isset($filters['coop']) ? (string)$filters['coop'] : '';
$ticketFiltro        = isset($filters['ticket']) ? (string)$filters['ticket'] : '';
$departamentoFiltro  = isset($filters['departamento']) ? (string)$filters['departamento'] : '';

$tiposConfig = [];
foreach ($tiposPorDepartamento as $deptId => $listaTipos) {
    if (!is_array($listaTipos)) {
        continue;
    }
    $deptKey = (string)$deptId;
    $tiposConfig[$deptKey] = [];
    foreach ($listaTipos as $tipoItem) {
        if (!is_array($tipoItem)) {
            continue;
        }
        $tiposConfig[$deptKey][] = [
            'id'     => isset($tipoItem['id']) ? (int)$tipoItem['id'] : 0,
            'nombre' => isset($tipoItem['nombre']) ? (string)$tipoItem['nombre'] : '',
            'globalId' => isset($tipoItem['global_id']) ? (int)$tipoItem['global_id'] : 0,
        ];
    }
}

$tiposJson = json_encode($tiposConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($tiposJson === false) {
    $tiposJson = '{}';
}

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
      <h1 id="incidencias-title" class="ent-title">Incidencias</h1>
      <p class="ent-toolbar__caption" aria-live="polite">
        <?= h((string)$total) ?> incidencias · Página <?= h((string)$page) ?> de <?= h((string)$pages) ?>
      </p>
    </div>
  </header>

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
          <label for="incidencias-departamento">Departamento</label>
          <select id="incidencias-departamento" name="departamento">
            <option value="">Todos</option>
            <?php foreach ($departamentos as $departamento): ?>
              <?php $depValue = isset($departamento['id']) ? (string)$departamento['id'] : ''; ?>
              <option value="<?= h($depValue) ?>" <?= $depValue === $departamentoFiltro ? 'selected' : '' ?>><?= h($departamento['nombre'] ?? '') ?></option>
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
                  data-incidencia-create-open
                  aria-haspopup="dialog"
                  aria-controls="incidencias-create-modal">
            <span class="material-symbols-outlined" aria-hidden="true">note_add</span>
            Nueva
          </button>
        </div>
      </form>

      <?php if (empty($items)): ?>
        <div class="card incidencias-empty" role="status">No hay incidencias registradas con los filtros seleccionados.</div>
      <?php else: ?>
        <div class="incidencias-cards" role="list" aria-label="Listado de incidencias">
          <?php foreach ($items as $row): ?>
            <?php
              $id            = isset($row['id']) ? (int)$row['id'] : 0;
              $fecha         = isset($row['creado_en']) ? (string)$row['creado_en'] : '';
              $fechaMostrar  = $fecha !== '' ? date('Y-m-d', strtotime($fecha)) : date('Y-m-d');
              $coopNombre    = (string)($row['cooperativa'] ?? '');
              $departamentoNombre = (string)($row['departamento_nombre'] ?? '');
              $departamentoId     = isset($row['departamento_id']) ? (int)$row['departamento_id'] : 0;
              $tipoDepartamentoId = isset($row['tipo_departamento_id']) ? (int)$row['tipo_departamento_id'] : 0;
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
              <article class="incidencias-card-item incidencias-row" role="listitem"
                 data-id="<?= h((string)$id) ?>"
                 data-fecha="<?= h($fechaMostrar) ?>"
                 data-cooperativa="<?= h($coopNombre) ?>"
                 data-departamento="<?= h($departamentoNombre) ?>"
                 data-departamento-id="<?= h((string)$departamentoId) ?>"
                 data-asunto="<?= h($asunto) ?>"
                 data-prioridad="<?= h($prioridad) ?>"
                 data-estado="<?= h($estadoActual) ?>"
                 data-ticket="<?= h($ticketDisplay) ?>"
                 data-tipo-id="<?= h((string)$tipoDepartamentoId) ?>"
                 data-tipo-global-id="<?= h((string)($row['tipo_global_id'] ?? '')) ?>"
                 data-tipo="<?= h($tipo) ?>"
                 data-descripcion="<?= h($descripcion) ?>"
                 data-contacto-nombre="<?= h($contactoNombre) ?>"
                 data-contacto-correo="<?= h($contactoCorreo) ?>"
                 data-contacto-telefono="<?= h($contactoTel) ?>"
                 data-contacto-cargo="<?= h($contactoCargo) ?>"
                 data-contacto-fecha="<?= h($contactoFecha) ?>">
                <header class="incidencias-card-item__header">
                  <div class="incidencias-card-item__title">
                    <span class="incidencias-card-item__label">Asunto</span>
                    <h3><?= h($asunto) ?></h3>
                  </div>
                  <span class="incidencias-badge incidencias-badge--<?= strtolower(str_replace(' ', '-', $estadoActual)) ?>"><?= h($estadoActual) ?></span>
                </header>

                <div class="incidencias-card-item__meta">
                  <div>
                    <span class="incidencias-card-item__label">Fecha</span>
                    <span class="incidencias-card-item__value"><?= h($fechaMostrar) ?></span>
                  </div>
                  <div>
                    <span class="incidencias-card-item__label">Ticket</span>
                    <span class="incidencias-card-item__value incidencias-card-item__value--ticket"><?= h($ticketDisplay) ?></span>
                  </div>
                  <div>
                    <span class="incidencias-card-item__label">Prioridad</span>
                    <span class="incidencias-card-item__value"><?= h($prioridad) ?></span>
                  </div>
                </div>

                <div class="incidencias-card-item__grid">
                  <div>
                    <span class="incidencias-card-item__label">Cooperativa</span>
                    <p><?= h($coopNombre) ?></p>
                  </div>
                  <div>
                    <span class="incidencias-card-item__label">Departamento</span>
                    <p><?= h($departamentoNombre) ?></p>
                  </div>
                  <div>
                    <span class="incidencias-card-item__label">Tipo de incidencia</span>
                    <p><?= h($tipo !== '' ? $tipo : '—') ?></p>
                  </div>
                </div>

                <footer class="incidencias-card-item__footer">
                  <div class="incidencias-card-item__ticket">
                    <span class="material-symbols-outlined" aria-hidden="true">description</span>
                    <span><?= h($ticketDisplay) ?></span>
                  </div>
                  <div class="incidencias-card-item__actions">
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
                  </div>
                </footer>
              </article>
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
</section>

<div class="incidencias-modal" id="incidencias-create-modal" role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1" hidden>
  <div class="incidencias-modal__overlay" data-incidencia-close></div>
  <div class="incidencias-modal__card" role="document">
    <header class="incidencias-modal__header">
      <h2><span class="material-symbols-outlined" aria-hidden="true">note_add</span> Nueva incidencia</h2>
      <button type="button" class="incidencias-modal__close" aria-label="Cerrar" data-incidencia-close>&times;</button>
    </header>
    <form class="incidencias-modal__form" id="incidencias-create-form" method="post" action="/comercial/incidencias" autocomplete="off">
      <div class="incidencias-modal__grid">
        <div class="incidencias-modal__field">
          <label for="create-cooperativa">Cooperativa</label>
          <select id="create-cooperativa" name="id_cooperativa" required>
            <option value="">Seleccione</option>
            <?php foreach ($cooperativas as $coop): ?>
              <option value="<?= h((string)($coop['id'] ?? '')) ?>"><?= h($coop['nombre'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field">
          <label for="create-departamento">Departamento</label>
          <select id="create-departamento" name="departamento_id" required>
            <option value="">Seleccione</option>
            <?php foreach ($departamentos as $departamento): ?>
              <option value="<?= h((string)($departamento['id'] ?? '')) ?>"><?= h($departamento['nombre'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field">
          <label for="create-asunto">Asunto</label>
          <input id="create-asunto" type="text" name="asunto" required maxlength="180" placeholder="Resumen de la incidencia">
        </div>
        <div class="incidencias-modal__field">
          <label for="create-tipo">Incidencia</label>
          <select id="create-tipo" name="tipo_incidencia_id" required disabled>
            <option value="">Seleccione un departamento</option>
          </select>
          <input type="hidden" name="tipo_incidencia_global_id" id="create-tipo-global" value="0">
        </div>
        <div class="incidencias-modal__field">
          <label for="create-prioridad">Prioridad</label>
          <select id="create-prioridad" name="prioridad" required>
            <?php foreach ($prioridades as $prioridad): ?>
              <option value="<?= h($prioridad) ?>"><?= h($prioridad) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field incidencias-modal__field--full">
          <label for="create-descripcion">Descripción</label>
          <textarea id="create-descripcion" name="descripcion" rows="3" placeholder="Detalles adicionales"></textarea>
        </div>
      </div>
      <div class="incidencias-modal__actions">
        <button class="btn btn-outline" type="button" data-incidencia-close>
          <span class="material-symbols-outlined" aria-hidden="true">close</span>
          Cancelar
        </button>
        <button class="btn btn-primary" type="submit">
          <span class="material-symbols-outlined" aria-hidden="true">save</span>
          Guardar
        </button>
      </div>
    </form>
  </div>
</div>

<div class="incidencias-modal" id="incidencias-modal" role="dialog" aria-modal="true" aria-hidden="true" tabindex="-1" hidden>
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
          <label for="modal-departamento">Departamento</label>
          <select id="modal-departamento" name="departamento_id" disabled>
            <option value="">Seleccione</option>
            <?php foreach ($departamentos as $departamento): ?>
              <option value="<?= h((string)($departamento['id'] ?? '')) ?>"><?= h($departamento['nombre'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-asunto">Asunto</label>
          <input id="modal-asunto" name="asunto" type="text" readonly required>
        </div>
        <div class="incidencias-modal__field">
          <label for="modal-tipo">Incidencia</label>
          <select id="modal-tipo" name="tipo_incidencia_id" disabled>
            <option value="">Seleccione un departamento</option>
          </select>
          <input type="hidden" name="tipo_incidencia_global_id" id="modal-tipo-global" value="0">
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

<script>
  window.__INCIDENCIAS_CONFIG__ = Object.assign({}, window.__INCIDENCIAS_CONFIG__ || {}, {
    tipos: <?= $tiposJson ?>
  });
</script>
<script src="/js/incidencias.js" defer></script>
