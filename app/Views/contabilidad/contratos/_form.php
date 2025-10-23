<?php
if (!function_exists('contab_h')) {
    function contab_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$errors    = is_array($errors ?? null) ? $errors : [];
$old       = is_array($old ?? null) ? $old : [];
$item      = is_array($item ?? null) ? $item : [];
$entidades = is_array($entidades ?? null) ? $entidades : [];
$servicios = is_array($servicios ?? null) ? $servicios : [];
$redes     = is_array($redes ?? null) ? $redes : [];
$estados   = is_array($estados ?? null) ? $estados : [];
$periodos  = is_array($periodos ?? null) ? $periodos : [];
$tiposContrato = is_array($tiposContrato ?? null) ? $tiposContrato : $periodos;

$value = static function (string $key, $default = null) use ($old, $item) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    if (array_key_exists($key, $item)) {
        return $item[$key];
    }
    return $default;
};

function contab_field(string $name, array $errors): string
{
    return isset($errors[$name]) ? ' is-invalid' : '';
}

$servSel = $value('servicios_ids', $value('servicios', []));
if (!is_array($servSel)) {
  $servSel = [];
}
$servSel = array_map('intval', $servSel);

$ivaDefault = $value('iva_porcentaje', 15);
if (!is_numeric($ivaDefault)) {
  $ivaDefault = 15;
}
$ivaDefault = (float)$ivaDefault;

$activoRaw = $value('activo', true);
$activoActual = !in_array(strtolower((string)$activoRaw), ['0', 'false', 'off', ''], true);
?>

<div class="form-grid form-grid--three">
  <div class="form-field">
    <label for="contrato-entidad">Entidad *</label>
    <select id="contrato-entidad" name="id_cooperativa" required aria-describedby="hint-contrato-entidad" class="select<?= contab_field('id_cooperativa', $errors) ?>">
      <option value="">Seleccione</option>
      <?php foreach ($entidades as $entidad): ?>
        <?php $id = (int)($entidad['id'] ?? 0); ?>
        <option value="<?= $id ?>" <?= ((int)$value('id_cooperativa', 0) === $id) ? 'selected' : '' ?>>
          <?= contab_h($entidad['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-contrato-entidad" class="form-hint">Elige la entidad responsable del contrato.</span>
    <?php if (isset($errors['id_cooperativa'])): ?><small class="text-error"><?= contab_h($errors['id_cooperativa']) ?></small><?php endif; ?>
  </div>

  <div class="form-field form-field--full">
    <span class="form-label" id="contrato-servicios-label">Servicios *</span>
    <div class="chips" role="group" aria-labelledby="contrato-servicios-label">
      <?php foreach ($servicios as $servicio): ?>
        <?php
          $sid = (int)($servicio['id'] ?? 0);
          $sname = (string)($servicio['nombre'] ?? '');
          $checked = in_array($sid, $servSel, true);
        ?>
        <label class="chip<?= $checked ? ' is-checked' : '' ?>">
          <input type="checkbox" name="servicios[]" value="<?= $sid ?>" <?= $checked ? 'checked' : '' ?>>
          <span><?= contab_h($sname) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <span id="hint-contrato-servicio" class="form-hint">Selecciona uno o varios servicios vinculados al contrato.</span>
    <?php if (isset($errors['id_servicio'])): ?><small class="text-error"><?= contab_h($errors['id_servicio']) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="contrato-red">Red</label>
    <select id="contrato-red" name="codigo_red" aria-describedby="hint-contrato-red" class="select">
      <option value="">Sin especificar</option>
      <?php foreach ($redes as $red): ?>
        <?php $codigo = (string)($red['codigo'] ?? ''); ?>
        <option value="<?= contab_h($codigo) ?>" <?= ($value('codigo_red', '') === $codigo) ? 'selected' : '' ?>>
          <?= contab_h($red['nombre'] ?? $codigo) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-contrato-red" class="form-hint">Si aplica, selecciona la red para segmentar la facturación.</span>
  </div>

  <div class="form-field">
    <label for="contrato-periodo">Periodo *</label>
    <select id="contrato-periodo" name="periodo" required aria-describedby="hint-contrato-periodo" class="select<?= contab_field('periodo_facturacion', $errors) ?>">
      <option value="">Seleccione</option>
      <?php foreach ($periodos as $periodo): ?>
        <option value="<?= contab_h($periodo) ?>" <?= (strcasecmp($value('periodo_facturacion', ''), $periodo) === 0) ? 'selected' : '' ?>>
          <?= contab_h($periodo) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-contrato-periodo" class="form-hint">Define la frecuencia de facturación (mensual, anual, etc.).</span>
    <?php if (isset($errors['periodo_facturacion'])): ?><small class="text-error"><?= contab_h($errors['periodo_facturacion']) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="contrato-tipo">Tipo de contrato *</label>
    <select id="contrato-tipo" name="tipo_contrato" required aria-describedby="hint-contrato-tipo" class="select<?= contab_field('tipo_contrato', $errors) ?>">
      <option value="">Seleccione</option>
      <?php foreach ($tiposContrato as $tipo): ?>
        <option value="<?= contab_h($tipo) ?>" <?= (strcasecmp($value('tipo_contrato', ''), $tipo) === 0) ? 'selected' : '' ?>>
          <?= contab_h($tipo) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-contrato-tipo" class="form-hint">Especifica el esquema contractual (mensual, trimestral, anual o indefinido).</span>
    <?php if (isset($errors['tipo_contrato'])): ?><small class="text-error"><?= contab_h($errors['tipo_contrato']) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="contrato-estado">Estado</label>
    <select id="contrato-estado" name="estado" aria-describedby="hint-contrato-estado" class="select">
      <?php foreach ($estados as $estado): ?>
        <option value="<?= contab_h($estado) ?>" <?= (strcasecmp($value('estado_pago', ''), $estado) === 0) ? 'selected' : '' ?>>
          <?= contab_h(ucfirst(strtolower($estado))) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-contrato-estado" class="form-hint">Indica el estado de facturación del contrato.</span>
  </div>

  <div class="form-field">
    <label for="contrato-fecha">Fecha de suscripción *</label>
    <input id="contrato-fecha" type="date" name="fecha_contratacion" required aria-describedby="hint-contrato-fecha" value="<?= contab_h($value('fecha_contratacion', '')) ?>" class="<?= contab_field('fecha_contratacion', $errors) ?>">
    <span id="hint-contrato-fecha" class="form-hint">Fecha en la que inicia la relación contractual.</span>
    <?php if (isset($errors['fecha_contratacion'])): ?><small class="text-error"><?= contab_h($errors['fecha_contratacion']) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="contrato-caducidad">Fecha de caducidad</label>
    <input id="contrato-caducidad" type="date" name="fecha_caducidad" aria-describedby="hint-contrato-caducidad" value="<?= contab_h($value('fecha_caducidad', '')) ?>">
    <span id="hint-contrato-caducidad" class="form-hint">Registra cuándo finaliza la vigencia pactada.</span>
  </div>

  <div class="form-field">
    <label for="contrato-terminacion">Detalle de terminación</label>
    <input id="contrato-terminacion" type="text" name="terminacion_contrato" maxlength="255" placeholder="Ej.: Renovación automática, cancelado por cliente..." value="<?= contab_h((string)$value('terminacion_contrato', '')) ?>">
    <span class="form-hint">Describe cómo se dará por terminado el contrato (opcional).</span>
  </div>

  <div class="form-field">
    <label for="contrato-finalizacion">Fecha de terminación</label>
    <input id="contrato-finalizacion" type="date" name="fecha_finalizacion" aria-describedby="hint-contrato-finalizacion" value="<?= contab_h($value('fecha_finalizacion', '')) ?>" class="<?= contab_field('fecha_finalizacion', $errors) ?>">
    <span id="hint-contrato-finalizacion" class="form-hint">Úsala cuando exista una fecha pactada de terminación del contrato.</span>
    <?php if (isset($errors['fecha_finalizacion'])): ?><small class="text-error"><?= contab_h($errors['fecha_finalizacion']) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="contrato-desvinculacion">Fecha de desvinculación</label>
    <input id="contrato-desvinculacion" type="date" name="fecha_desvinculacion" aria-describedby="hint-contrato-desvinculacion" value="<?= contab_h($value('fecha_desvinculacion', '')) ?>">
    <span id="hint-contrato-desvinculacion" class="form-hint">Solo úsala si el servicio ya fue dado de baja.</span>
  </div>

  <div class="form-field">
    <label for="contrato-licencias">Número de licencias</label>
    <input id="contrato-licencias" type="number" name="numero_licencias" min="0" placeholder="Ej.: 25" value="<?= contab_h((string)$value('numero_licencias', '')) ?>">
    <span class="form-hint">Indica cuántos accesos o licencias incluye el contrato.</span>
  </div>

  <div class="form-field">
    <label for="contrato-ultimo-pago">Fecha último pago</label>
    <input id="contrato-ultimo-pago" type="date" name="fecha_ultimo_pago" aria-describedby="hint-contrato-ultimo-pago" value="<?= contab_h($value('fecha_ultimo_pago', '')) ?>">
    <span id="hint-contrato-ultimo-pago" class="form-hint">Mantén trazabilidad del último pago recibido.</span>
  </div>

  <div class="form-field">
    <label for="contrato-valor-base">Valor base *</label>
    <input id="contrato-valor-base" type="number" step="0.01" name="valor_base" required placeholder="Ej.: 1500.00" aria-describedby="hint-contrato-valor-base" value="<?= contab_h((string)$value('valor_base', $value('valor_contratado', ''))) ?>" class="<?= contab_field('valor_base', $errors) ?>" data-contract-base>
    <span id="hint-contrato-valor-base" class="form-hint">Monto sin impuestos que servirá para calcular el IVA.</span>
    <?php if (isset($errors['valor_base'])): ?><small class="text-error"><?= contab_h($errors['valor_base']) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="contrato-iva-rate">IVA (%)</label>
    <input id="contrato-iva-rate" type="number" step="0.01" name="iva_porcentaje" min="0" max="100" value="<?= contab_h(number_format($ivaDefault, 2, '.', '')) ?>" data-contract-iva-rate>
    <span class="form-hint">Porcentaje aplicado al monto base (por defecto 15%).</span>
  </div>

  <div class="form-field">
    <label for="contrato-valor-individual">Valor individual</label>
    <input id="contrato-valor-individual" type="number" step="0.01" name="valor_individual" placeholder="Ej.: 45.00" value="<?= contab_h((string)$value('valor_individual', '')) ?>">
    <span class="form-hint">Costo por cada usuario o licencia, si aplica.</span>
  </div>

  <div class="form-field">
    <label for="contrato-valor-grupal">Valor grupal</label>
    <input id="contrato-valor-grupal" type="number" step="0.01" name="valor_grupal" placeholder="Ej.: 320.00" value="<?= contab_h((string)$value('valor_grupal', '')) ?>">
    <span class="form-hint">Utiliza este campo si existe un paquete o valor corporativo.</span>
  </div>

  <div class="form-field">
    <label for="contrato-valor-iva">IVA calculado</label>
    <input id="contrato-valor-iva" type="number" step="0.01" name="valor_iva" placeholder="Calculado automáticamente" value="<?= contab_h((string)$value('valor_iva', '')) ?>" data-contract-iva>
    <span class="form-hint">Se calcula con base en el porcentaje indicado, pero puedes ajustarlo manualmente.</span>
  </div>

  <div class="form-field">
    <label for="contrato-valor-total">Total</label>
    <input id="contrato-valor-total" type="number" step="0.01" name="valor_total" placeholder="Base + IVA" value="<?= contab_h((string)$value('valor_total', '')) ?>" data-contract-total>
    <span class="form-hint">Suma final que se facturará al cliente (IVA incluido).</span>
  </div>

  <div class="form-field">
    <label for="contrato-observaciones">Observaciones</label>
    <textarea id="contrato-observaciones" name="observaciones" rows="3" placeholder="Notas adicionales, acuerdos especiales, renovaciones automáticas..."><?= contab_h($value('observaciones', '')) ?></textarea>
    <span class="form-hint">Resalta información clave que deba revisar el equipo contable.</span>
  </div>

  <div class="form-field">
    <label for="contrato-documento">Comprobante (PDF o imagen)</label>
    <input id="contrato-documento" type="file" name="documento" accept="application/pdf,image/*">
    <span class="form-hint">Adjunta el contrato firmado, factura u otro soporte.</span>
    <?php if (isset($errors['documento'])): ?><small class="text-error"><?= contab_h($errors['documento']) ?></small><?php endif; ?>
    <?php if (!empty($value('documento_contable'))): ?>
      <p class="form-hint">Actual: <a href="/storage/<?= contab_h($value('documento_contable')) ?>" target="_blank" rel="noopener">Ver comprobante</a></p>
    <?php endif; ?>
  </div>

  <div class="form-field form-field--full contract-status">
    <span class="form-label">Estado del contrato</span>
    <label class="switch-toggle<?= $activoActual ? ' is-active' : '' ?>">
      <input type="checkbox" name="activo" value="1" <?= $activoActual ? 'checked' : '' ?> data-contract-active>
      <span class="switch-track" aria-hidden="true">
        <span class="switch-label switch-label--on">Activo</span>
        <span class="switch-handle"></span>
        <span class="switch-label switch-label--off">Inactivo</span>
      </span>
    </label>
    <span class="form-hint">Desactiva el contrato cuando se encuentre suspendido o finalizado.</span>
  </div>
</div>
