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
$servicios  = isset($servicios) && is_array($servicios) ? $servicios : [];
$redes      = isset($redes) && is_array($redes) ? $redes : [];
$historialEstados = isset($historialEstados) && is_array($historialEstados) ? $historialEstados : [];
$toastData  = isset($toast) && is_array($toast) ? $toast : null;

$pagination = Pagination::fromRequest([
    'page'    => (int)($page ?? 1),
    'perPage' => (int)($perPage ?? 10),
], 1, max(5, (int)($perPage ?? 10)), (int)($total ?? 0));

$page    = $pagination->page;
$perPage = $pagination->perPage;
$pages   = $pagination->pages();
$prev    = max(1, $page - 1);
$next    = min($pages, $page + 1);
$basePath = '/contabilidad/contratos';

function buildContratoPageUrl(int $pageNumber, array $filters, int $perPage, string $basePath): string
{
    $query = array_merge($filters, [
        'page'    => $pageNumber,
        'perPage' => $perPage,
    ]);
    $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return $basePath . ($queryString !== '' ? '?' . $queryString : '');
}

include __DIR__ . '/../../partials/breadcrumbs.php';
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="ent-container" aria-labelledby="contratos-title">
  <?php if ($toastData && ($toastData['message'] ?? '') !== ''): ?>
    <div id="ent-toast" class="ent-toast" role="status" aria-live="polite">
      <?= contab_h((string)$toastData['message']) ?>
    </div>
  <?php endif; ?>

  <header class="ent-toolbar">
    <div class="ent-toolbar__lead">
      <h1 id="contratos-title" class="ent-title">Contratos digitales</h1>
      <p class="ent-toolbar__caption">Total: <?= contab_h((string)$total) ?> contratos</p>
    </div>
    <div class="ent-toolbar__actions">
      <a class="btn btn-primary" href="<?= $basePath ?>/crear">
        <span class="material-symbols-outlined" aria-hidden="true">add</span>
        Nuevo contrato
      </a>
    </div>
  </header>

  <form class="ent-search ent-search--filters" action="<?= $basePath ?>" method="get" role="search">
    <div class="ent-search__field">
      <label for="contratos-q">Buscar</label>
      <input
        id="contratos-q"
        type="text"
        name="q"
        value="<?= contab_h($filters['q'] ?? '') ?>"
        placeholder="Entidad o servicio"
        autocomplete="off"
        spellcheck="false"
        data-typeahead="generic"
        data-suggest-url="/contabilidad/entidades/sugerencias"
        data-suggest-min="3"
        data-suggest-value="term"
        data-suggest-label="label"
        list="contratos-search-suggestions">
      <datalist id="contratos-search-suggestions"></datalist>
    </div>
    <div class="ent-search__field">
      <label for="contratos-servicio">Servicio</label>
      <select id="contratos-servicio" name="servicio">
        <option value="">Todos</option>
        <?php foreach ($servicios as $servicio): ?>
          <?php $sid = isset($servicio['id']) ? (int)$servicio['id'] : 0; ?>
          <option value="<?= $sid ?>" <?= ((int)($filters['servicio'] ?? 0) === $sid) ? 'selected' : '' ?>>
            <?= contab_h($servicio['nombre'] ?? '') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="ent-search__field">
      <label for="contratos-red">Red</label>
      <select id="contratos-red" name="red">
        <option value="">Todas</option>
        <?php foreach ($redes as $red): ?>
          <?php $codigo = (string)($red['codigo'] ?? ''); ?>
          <option value="<?= contab_h($codigo) ?>" <?= (($filters['red'] ?? '') === $codigo) ? 'selected' : '' ?>>
            <?= contab_h($red['nombre'] ?? $codigo) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="ent-search__field">
      <label for="contratos-estado">Estado</label>
      <select id="contratos-estado" name="estado">
        <option value="">Todos</option>
        <?php foreach (['PENDIENTE','PAGADO','VENCIDO','ANULADO'] as $estado): ?>
          <option value="<?= $estado ?>" <?= (strtoupper((string)($filters['estado'] ?? '')) === $estado) ? 'selected' : '' ?>>
            <?= contab_h(ucfirst(strtolower($estado))) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="ent-search__actions ent-search__actions--filters">
      <button class="btn btn-outline" type="submit">
        <span class="material-symbols-outlined" aria-hidden="true">search</span>
        Buscar
      </button>
      <a class="btn btn-ghost" href="<?= $basePath ?>">Limpiar</a>
    </div>
  </form>

  <?php if (empty($items)): ?>
    <div class="card" role="status" aria-live="polite">No se registraron contratos con los filtros actuales.</div>
  <?php else: ?>
    <div class="contratos-grid">
      <?php foreach ($items as $item): ?>
        <?php
          $serviciosItem = isset($item['servicios']) && is_array($item['servicios']) ? $item['servicios'] : [];
          $serviciosResumen = !empty($serviciosItem) ? implode(', ', $serviciosItem) : (string)($item['servicio'] ?? '');
          $terminacionLabel = trim((string)($item['terminacion_contrato'] ?? ''));
          $fechaInicio = isset($item['fecha_contratacion']) ? (string)$item['fecha_contratacion'] : '';
          $fechaCaducidad = isset($item['fecha_caducidad']) ? (string)$item['fecha_caducidad'] : '';
          $fechaTerminacion = isset($item['fecha_finalizacion']) ? (string)$item['fecha_finalizacion'] : '';
          $estadoPagoSlug = strtolower(str_replace(' ', '-', (string)($item['estado_pago'] ?? '')));
        ?>
        <article class="contrato-card">
          <header class="contrato-card__header">
            <div>
              <h2><?= contab_h($item['cooperativa'] ?? 'Entidad') ?></h2>
              <p class="contrato-card__subtitle">Suscripción: <?= contab_h($fechaInicio ?: '—') ?></p>
            </div>
            <span class="contrato-chip contrato-chip--estado estado-<?= contab_h($estadoPagoSlug) ?>">
              <?= contab_h($item['estado_pago'] ?? '') ?>
            </span>
          </header>

          <div class="contrato-card__meta">
            <span class="contrato-chip">Tipo: <?= contab_h($item['tipo_contrato'] ?? '—') ?></span>
            <span class="contrato-chip">Red: <?= contab_h($item['red_nombre'] ?? $item['codigo_red'] ?? 'Sin red') ?></span>
          </div>

          <dl class="contrato-card__body">
            <div>
              <dt>Servicios</dt>
              <dd>
                <?php if (!empty($serviciosItem)): ?>
                  <span class="contrato-services">
                    <?php foreach ($serviciosItem as $svcNombre): ?>
                      <span class="contrato-chip contrato-chip--service"><?= contab_h($svcNombre) ?></span>
                    <?php endforeach; ?>
                  </span>
                <?php else: ?>
                  <?= contab_h($item['servicio'] ?? 'Sin servicios') ?>
                <?php endif; ?>
              </dd>
            </div>
            <div>
              <dt>Caducidad</dt>
              <dd><?= contab_h($fechaCaducidad ?: '—') ?></dd>
            </div>
            <div>
              <dt>Terminación</dt>
              <dd>
                <?php if ($terminacionLabel !== ''): ?>
                  <small><?= contab_h($terminacionLabel) ?></small><br>
                <?php endif; ?>
                <?= contab_h($fechaTerminacion ?: '—') ?>
              </dd>
            </div>
            <div>
              <dt>Total</dt>
              <dd>$<?= contab_h(number_format((float)($item['valor_total'] ?? 0), 2)) ?></dd>
            </div>
            <div>
              <dt>Pagos registrados</dt>
              <dd>
                <?= contab_h((string)($item['historial_count'] ?? 0)) ?>
                <?php if (!empty($item['historial_estado'])): ?>
                  <span class="contrato-chip contrato-chip--historial"><?= contab_h(ucfirst(strtolower((string)$item['historial_estado']))) ?></span>
                <?php endif; ?>
              </dd>
            </div>
          </dl>

          <footer class="contrato-card__footer">
            <div class="contrato-card__actions">
              <a class="btn btn-sm" href="<?= $basePath ?>/editar?id=<?= contab_h((string)($item['id'] ?? 0)) ?>">Editar</a>
              <button type="button" class="btn btn-sm btn-outline" data-action="mostrar-historial"
                      data-contrato-id="<?= contab_h((string)($item['id'] ?? 0)) ?>"
                      data-cooperativa-id="<?= contab_h((string)($item['id_cooperativa'] ?? 0)) ?>"
                      data-cooperativa="<?= contab_h($item['cooperativa'] ?? '') ?>"
                      data-servicio="<?= contab_h($serviciosResumen) ?>">
                Historial
              </button>
            </div>
            <form method="post" action="<?= $basePath ?>/eliminar" onsubmit="return confirm('¿Eliminar este contrato?');">
              <input type="hidden" name="id" value="<?= contab_h((string)($item['id'] ?? 0)) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
            </form>
          </footer>
        </article>
      <?php endforeach; ?>
    </div>

    <nav class="pagination" aria-label="Paginación">
      <?php if ($page > 1): ?>
        <a href="<?= contab_h(buildContratoPageUrl($prev, $filters, $perPage, $basePath)) ?>" rel="prev">&laquo; Anterior</a>
      <?php else: ?>
        <span class="disabled">&laquo; Anterior</span>
      <?php endif; ?>
      <span>Página <?= contab_h((string)$page) ?> de <?= contab_h((string)max(1, $pages)) ?></span>
      <?php if ($page < $pages): ?>
        <a href="<?= contab_h(buildContratoPageUrl($next, $filters, $perPage, $basePath)) ?>" rel="next">Siguiente &raquo;</a>
      <?php else: ?>
        <span class="disabled">Siguiente &raquo;</span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php
$historialEstadosJson = json_encode($historialEstados, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<script>
window.contabHistorialEstados = <?= $historialEstadosJson ?: '[]' ?>;
</script>
<div class="modal-overlay" data-historial-overlay hidden>
  <div class="modal-dialog" data-historial-modal role="dialog" aria-modal="true" aria-labelledby="historial-modal-title">
    <header class="modal-header">
      <h2 id="historial-modal-title">Historial de pagos</h2>
      <button type="button" class="btn btn-sm" data-historial-close>
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
    </header>
    <section class="modal-body">
      <p class="modal-subtitle" data-historial-contrato></p>
      <table class="table modal-table">
        <thead>
          <tr>
            <th>Periodo</th>
            <th>Emisión</th>
            <th>Monto</th>
            <th>Estado</th>
            <th>Comprobante</th>
            <th></th>
          </tr>
        </thead>
        <tbody data-historial-list></tbody>
      </table>
      <h3 class="modal-section-title">Registrar nuevo pago</h3>
      <form class="historial-form" data-historial-form enctype="multipart/form-data">
        <input type="hidden" name="id_cooperativa" data-historial-cooperativa>
        <input type="hidden" name="id_contratacion" data-historial-contrato-id>
        <div class="form-grid">
          <div class="form-field">
            <label for="hist-form-periodo">Periodo *</label>
            <input id="hist-form-periodo" type="text" name="periodo" required placeholder="Ej.: Febrero 2025">
            <span class="form-hint">Referencia de la factura o ciclo de cobro.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-emision">Emisión *</label>
            <input id="hist-form-emision" type="date" name="fecha_emision" required aria-describedby="hint-hist-emision">
            <span id="hint-hist-emision" class="form-hint">Fecha en que se emite la factura.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-vencimiento">Vencimiento</label>
            <input id="hist-form-vencimiento" type="date" name="fecha_vencimiento" aria-describedby="hint-hist-vencimiento">
            <span id="hint-hist-vencimiento" class="form-hint">Opcional, define la fecha límite de pago.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-pago">Fecha pago</label>
            <input id="hist-form-pago" type="date" name="fecha_pago" aria-describedby="hint-hist-pago">
            <span id="hint-hist-pago" class="form-hint">Registra cuándo se efectuó el pago, si corresponde.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-base">Monto base *</label>
            <input id="hist-form-base" type="number" step="0.01" name="monto_base" required placeholder="Ej.: 800.00" aria-describedby="hint-hist-base" data-historial-base>
            <span id="hint-hist-base" class="form-hint">Importe sin impuestos registrado en la factura.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-iva-rate">IVA (%)</label>
            <input id="hist-form-iva-rate" type="number" step="0.01" name="iva_porcentaje" value="15" min="0" max="100" aria-describedby="hint-hist-iva-rate" data-historial-iva-rate>
            <span id="hint-hist-iva-rate" class="form-hint">Porcentaje del impuesto aplicado al monto base.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-iva">IVA (15%)</label>
            <input id="hist-form-iva" type="number" step="0.01" name="monto_iva" placeholder="Calculado automáticamente" data-historial-iva>
            <span class="form-hint">Ajusta el valor calculado si se aplica otro porcentaje.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-total">Total</label>
            <input id="hist-form-total" type="number" step="0.01" name="monto_total" placeholder="Base + IVA" data-historial-total>
            <span class="form-hint">Suma final facturada (incluye IVA y descuentos).</span>
          </div>
          <div class="form-field">
            <label for="hist-form-estado">Estado</label>
            <select id="hist-form-estado" name="estado" data-historial-estado aria-describedby="hint-hist-estado"></select>
            <span id="hint-hist-estado" class="form-hint">Selecciona si el pago está pendiente, vencido o pagado.</span>
          </div>
          <div class="form-field">
            <label for="hist-form-comprobante">Comprobante</label>
            <input id="hist-form-comprobante" type="file" name="comprobante" accept="application/pdf,image/*">
            <span class="form-hint">Adjunta el comprobante de pago o la factura escaneada.</span>
          </div>
          <div class="form-field form-field--full">
            <label for="hist-form-observaciones">Observaciones</label>
            <textarea id="hist-form-observaciones" name="observaciones" rows="2" placeholder="Notas internas sobre el pago, retenciones o acuerdos."></textarea>
            <span class="form-hint">Comparte información útil para seguimiento futuro.</span>
          </div>
        </div>
        <div class="form-actions ent-actions">
          <button class="btn btn-primary" type="submit">Añadir pago</button>
        </div>
      </form>
    </section>
  </div>
</div>
<script src="/js/search-typeahead.js" defer></script>
<script src="/js/contabilidad-contratos.js" defer></script>
