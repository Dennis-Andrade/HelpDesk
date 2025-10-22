<?php
if (!function_exists('contab_h')) {
    function contab_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$errors     = is_array($errors ?? null) ? $errors : [];
$item       = is_array($item ?? null) ? $item : [];
$old        = is_array($old ?? null) ? $old : [];
$entidades  = is_array($entidades ?? null) ? $entidades : [];
$provincias = is_array($provincias ?? null) ? $provincias : [];
$cantones   = is_array($cantones ?? null) ? $cantones : [];

$value = static function (string $key, $default = '') use ($old, $item) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    if (array_key_exists($key, $item)) {
        return $item[$key];
    }
    return $default;
};

function fieldError(string $name, array $errors): ?string
{
    return $errors[$name] ?? null;
}
?>

<div class="form-grid">
  <div class="form-field">
    <label for="fact-cooperativa">Entidad *</label>
    <select id="fact-cooperativa" name="id_cooperativa" required aria-describedby="hint-fact-entidad" class="select">
      <option value="">Seleccione</option>
      <?php foreach ($entidades as $entidad): ?>
        <?php $id = (int)($entidad['id'] ?? 0); ?>
        <option value="<?= $id ?>" <?= ((int)$value('id_cooperativa', 0) === $id) ? 'selected' : '' ?>>
          <?= contab_h($entidad['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-fact-entidad" class="form-hint">Selecciona la entidad para sincronizar la información contable.</span>
  </div>

  <div class="form-field">
    <label for="fact-direccion">Dirección *</label>
    <input id="fact-direccion" type="text" name="direccion" required placeholder="Calle principal, número y referencia" value="<?= contab_h($value('direccion', '')) ?>" class="<?= fieldError('direccion', $errors) ? 'is-invalid' : '' ?>">
    <span class="form-hint">Dirección oficial que aparecerá en las facturas emitidas.</span>
    <?php if ($msg = fieldError('direccion', $errors)): ?><small class="text-error"><?= contab_h($msg) ?></small><?php endif; ?>
  </div>

  <div class="form-field">
    <label for="fact-provincia">Provincia</label>
    <select id="fact-provincia" name="provincia_id" class="select" data-cantones-url="/shared/cantones" aria-describedby="hint-fact-provincia">
      <option value="">Seleccione</option>
      <?php foreach ($provincias as $provincia): ?>
        <?php $pid = (int)($provincia['id'] ?? 0); ?>
        <option value="<?= $pid ?>" <?= ((int)$value('provincia_id', 0) === $pid) ? 'selected' : '' ?>>
          <?= contab_h($provincia['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-fact-provincia" class="form-hint">Puedes seleccionar una provincia oficial o escribirla manualmente.</span>
  </div>

  <div class="form-field">
    <label for="fact-canton">Cantón</label>
    <select id="fact-canton" name="canton_id" class="select" aria-describedby="hint-fact-canton">
      <option value="">Seleccione</option>
      <?php foreach ($cantones as $canton): ?>
        <?php $cid = (int)($canton['id'] ?? 0); ?>
        <option value="<?= $cid ?>" <?= ((int)$value('canton_id', 0) === $cid) ? 'selected' : '' ?>>
          <?= contab_h($canton['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <span id="hint-fact-canton" class="form-hint">Si no encuentras el cantón, complétalo en los campos de texto siguientes.</span>
  </div>

  <div class="form-field">
    <label for="fact-provincia-texto">Provincia (texto)</label>
    <input id="fact-provincia-texto" type="text" name="provincia" placeholder="Provincia escrita tal como desea en la factura" value="<?= contab_h($value('provincia', '')) ?>">
    <span class="form-hint">Úsalo si necesitas personalizar el nombre que se imprimirá.</span>
  </div>

  <div class="form-field">
    <label for="fact-canton-texto">Cantón (texto)</label>
    <input id="fact-canton-texto" type="text" name="canton" placeholder="Cantón personalizado" value="<?= contab_h($value('canton', '')) ?>">
    <span class="form-hint">Ideal cuando el cantón no existe en el catálogo oficial.</span>
  </div>

  <?php for ($i = 1; $i <= 5; $i++): ?>
    <div class="form-field">
      <label for="fact-email<?= $i ?>">Correo corporativo <?= $i === 1 ? '*' : '' ?></label>
      <input id="fact-email<?= $i ?>" type="email" name="email<?= $i ?>" placeholder="Ej.: <?= $i === 1 ? 'contabilidad@empresa.com' : 'auxiliar' . $i . '@empresa.com' ?>" value="<?= contab_h($value('email' . $i, '')) ?>" class="<?= $i === 1 && fieldError('email1', $errors) ? 'is-invalid' : '' ?>">
      <span class="form-hint"><?= $i === 1 ? 'Correo principal para facturación electrónica.' : 'Contacto alternativo para notificaciones.' ?></span>
      <?php if ($i === 1 && ($msg = fieldError('email1', $errors))): ?><small class="text-error"><?= contab_h($msg) ?></small><?php endif; ?>
    </div>
  <?php endfor; ?>

  <?php for ($i = 1; $i <= 3; $i++): ?>
    <div class="form-field">
      <label for="fact-tel-fijo<?= $i ?>">Teléfono convencional <?= $i ?></label>
      <input id="fact-tel-fijo<?= $i ?>" type="text" name="tel_fijo<?= $i ?>" placeholder="Ej.: 02<?= 300000 + ($i * 123) ?>" value="<?= contab_h($value('tel_fijo' . $i, '')) ?>">
      <span class="form-hint">Teléfonos de oficina disponibles para el área contable.</span>
    </div>
  <?php endfor; ?>

  <?php for ($i = 1; $i <= 3; $i++): ?>
    <div class="form-field">
      <label for="fact-tel-cel<?= $i ?>">Teléfono celular <?= $i ?></label>
      <input id="fact-tel-cel<?= $i ?>" type="text" name="tel_cel<?= $i ?>" placeholder="Ej.: 09<?= 800000 + ($i * 321) ?>" value="<?= contab_h($value('tel_cel' . $i, '')) ?>">
      <span class="form-hint">Contactos móviles de referencia para cobros o coordinaciones.</span>
    </div>
  <?php endfor; ?>

  <div class="form-field">
    <label for="fact-cont-nombre">Contacto departamento contabilidad</label>
    <input id="fact-cont-nombre" type="text" name="contabilidad_nombre" placeholder="Ej.: Ana Pérez - Coordinadora" value="<?= contab_h($value('contabilidad_nombre', '')) ?>">
    <span class="form-hint">Persona de referencia para gestiones de cobro.</span>
  </div>

  <div class="form-field">
    <label for="fact-cont-telefono">Teléfono contacto contabilidad</label>
    <input id="fact-cont-telefono" type="text" name="contabilidad_telefono" placeholder="0991234567" value="<?= contab_h($value('contabilidad_telefono', '')) ?>" class="<?= fieldError('contabilidad_telefono', $errors) ? 'is-invalid' : '' ?>">
    <span class="form-hint">Número directo del responsable contable.</span>
    <?php if ($msg = fieldError('contabilidad_telefono', $errors)): ?><small class="text-error"><?= contab_h($msg) ?></small><?php endif; ?>
  </div>
</div>
