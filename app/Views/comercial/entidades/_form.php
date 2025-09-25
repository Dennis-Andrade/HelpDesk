<?php
/** @var array<int,array{id:int,nombre:string}> $provincias */
/** @var array<int,array{id:int,nombre:string}> $cantones */
/** @var array<string,string> $errors */
/** @var array<string,mixed> $item */
/** @var array<string,mixed> $old */

$errors = is_array($errors ?? null) ? $errors : array();
$item   = is_array($item ?? null) ? $item : array();
$old    = is_array($old ?? null) ? $old : array();

$provincias = is_array($provincias ?? null) ? $provincias : array();
$cantones   = is_array($cantones ?? null) ? $cantones : array();

$val = static function (string $key, $default = '') use ($item, $old) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    if (array_key_exists($key, $item)) {
        return $item[$key];
    }
    return $default;
};

$provSel = (int)$val('provincia_id', 0);
$cantSel = (int)$val('canton_id', 0);
?>
<div class="ent-form">
  <div class="ent-form__row">
    <label for="ent-nombre">Nombre *</label>
    <input id="ent-nombre" name="nombre" type="text" required value="<?= htmlspecialchars((string)$val('nombre'), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['nombre'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['nombre'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-ruc">RUC / Cédula</label>
    <input id="ent-ruc" name="ruc" type="text" inputmode="numeric" pattern="^\d{10,13}$" maxlength="13" value="<?= htmlspecialchars((string)$val('ruc', $val('nit')), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['ruc'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['ruc'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-telefono">Teléfono</label>
    <input id="ent-telefono" name="telefono" type="text" value="<?= htmlspecialchars((string)$val('telefono'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej.: 023456789 o 0991234567">
    <?php if (isset($errors['telefono'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['telefono'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-email">Email</label>
    <input id="ent-email" name="email" type="email" value="<?= htmlspecialchars((string)$val('email'), ENT_QUOTES, 'UTF-8') ?>" placeholder="ejemplo@dominio.com">
    <?php if (isset($errors['email'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-provincia">Provincia</label>
    <select id="ent-provincia" name="provincia_id" data-cantones-url="/shared/cantones">
      <option value="">Seleccione...</option>
      <?php foreach ($provincias as $provincia): ?>
        <?php $pid = (int)($provincia['id'] ?? 0); ?>
        <option value="<?= $pid ?>" <?= $pid === $provSel ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)($provincia['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="ent-form__row">
    <label for="ent-canton">Cantón</label>
    <select id="ent-canton" name="canton_id">
      <option value="">Seleccione...</option>
      <?php foreach ($cantones as $canton): ?>
        <?php $cid = (int)($canton['id'] ?? 0); ?>
        <option value="<?= $cid ?>" <?= $cid === $cantSel ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)($canton['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="ent-form__row">
    <label for="ent-segmento">Segmento</label>
    <input id="ent-segmento" name="segmento" type="text" value="<?= htmlspecialchars((string)$val('segmento'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej.: Cooperativa de ahorro y crédito">
  </div>
</div>

<script>
(function() {
  var provincia = document.getElementById('ent-provincia');
  var canton = document.getElementById('ent-canton');
  if (!provincia || !canton) {
    return;
  }

  var url = provincia.getAttribute('data-cantones-url') || '';
  if (!url) {
    return;
  }

  provincia.addEventListener('change', function () {
    var provinciaId = provincia.value;
    canton.innerHTML = '<option value="">Cargando...</option>';
    if (!provinciaId) {
      canton.innerHTML = '<option value="">Seleccione...</option>';
      return;
    }

    fetch(url + '?provincia_id=' + encodeURIComponent(provinciaId), {
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Error al cargar cantones');
      }
      return response.json();
    }).then(function (data) {
      var options = '<option value="">Seleccione...</option>';
      if (Array.isArray(data)) {
        data.forEach(function (item) {
          if (!item || typeof item.id === 'undefined') {
            return;
          }
          var nombre = item.nombre ? String(item.nombre) : '';
          options += '<option value="' + item.id + '">' + nombre.replace(/</g, '&lt;') + '</option>';
        });
      }
      canton.innerHTML = options;
    }).catch(function () {
      canton.innerHTML = '<option value="">Seleccione...</option>';
    });
  });
})();
</script>
