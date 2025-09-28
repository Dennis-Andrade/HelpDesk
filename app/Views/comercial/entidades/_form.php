<?php
/** Plantilla de Formulario Entidad (crear/editar) */

$errors = is_array($errors ?? null) ? $errors : [];
$item   = is_array($item ?? null) ? $item : [];
$old    = is_array($old ?? null) ? $old : [];

$provincias = is_array($provincias ?? null) ? $provincias : [];
$cantones   = is_array($cantones ?? null) ? $cantones : [];
$servicios  = is_array($servicios ?? null) ? $servicios : [];
$segmentosData = is_array($segmentos ?? null) ? $segmentos : [];
$isCreate = !empty($isCreate);

$provSel = (int)($item['provincia_id'] ?? $old['provincia_id'] ?? 0);
$cantSel = (int)($item['canton_id'] ?? $old['canton_id'] ?? 0);

$val = static function (string $key, $default = '') use ($old, $item) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }

    if (array_key_exists($key, $item)) {
        return $item[$key];
    }

    return $default;
};

$servSel = $val('servicios', []);
if (!is_array($servSel)) {
    $servSel = [];
}
$servSel = array_map('intval', $servSel);

$segmentoActual = (string)$val('id_segmento', '');
$segmentOptions = ['' => '-- N/A --'];
if (!empty($segmentosData)) {
    $segmentOptions = ['' => '-- N/A --'];
    foreach ($segmentosData as $segmento) {
        $sid = (string)($segmento['id'] ?? $segmento['id_segmento'] ?? '');
        $sname = (string)($segmento['nombre'] ?? $segmento['nombre_segmento'] ?? '');
        if ($sid === '') {
            continue;
        }
        $segmentOptions[$sid] = $sname !== '' ? $sname : ('Segmento ' . $sid);
    }
} else {
    $segmentOptions = [
        ''  => '-- N/A --',
        '1' => 'Segmento 1',
        '2' => 'Segmento 2',
        '3' => 'Segmento 3',
        '4' => 'Segmento 4',
        '5' => 'Segmento 5',
    ];
}

$tipoActual = (string)($item['tipo_entidad'] ?? $old['tipo_entidad'] ?? 'cooperativa');
$tiposEntidad = ['cooperativa', 'mutualista', 'sujeto_no_financiero', 'caja_ahorros', 'casa_valores'];
?>

<div class="ent-form__grid grid grid-2">
  <label class="col-span-2">
    Nombre * <?= isset($errors['nombre']) ? '<small class="text-error">' . $errors['nombre'] . '</small>' : '' ?>
    <input
      type="text"
      name="nombre"
      required
      placeholder="Ej.: COAC SAN JUAN LTDA"
      value="<?= htmlspecialchars((string)$val('nombre'), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <label>
    Cédula / RUC (10–13) <?= isset($errors['ruc']) ? '<small class="text-error">' . $errors['ruc'] . '</small>' : '' ?>
    <input
      type="text"
      name="ruc"
      inputmode="numeric"
      pattern="^\d{10,13}$"
      minlength="10"
      maxlength="13"
      title="Solo números, entre 10 y 13 dígitos"
      placeholder="Ej.: 1712345678 o 1790012345001"
      value="<?= htmlspecialchars((string)$val('ruc', $val('nit')), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <label>
    Teléfono fijo <?= isset($errors['telefono_fijo_1']) ? '<small class="text-error">' . $errors['telefono_fijo_1'] . '</small>' : '' ?>
    <input
      type="text"
      name="telefono_fijo_1"
      inputmode="numeric"
      pattern="^\d{7}$"
      minlength="7"
      maxlength="7"
      title="Solo números, 7 dígitos"
      placeholder="Ej.: 022345678"
      value="<?= htmlspecialchars((string)$val('telefono_fijo_1', $val('telefono_fijo')), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <label class="col-span-2">
    Celular <?= isset($errors['telefono_movil']) ? '<small class="text-error">' . $errors['telefono_movil'] . '</small>' : '' ?>
    <input
      type="text"
      name="telefono_movil"
      inputmode="numeric"
      pattern="^\d{10}$"
      minlength="10"
      maxlength="10"
      title="Solo números, 10 dígitos"
      placeholder="Ej.: 0998765432"
      value="<?= htmlspecialchars((string)$val('telefono_movil'), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <label class="col-span-2">
    Email <?= isset($errors['email']) ? '<small class="text-error">' . $errors['email'] . '</small>' : '' ?>
    <input
      type="email"
      name="email"
      placeholder="ejemplo@dominio.com"
      value="<?= htmlspecialchars((string)$val('email'), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <div class="grid-2 col-span-2 ent-form__row">
    <label>
      Provincia
      <select
        id="provincia_id"
        name="provincia_id"
        class="select"
        data-cantones-url="/shared/cantones">
        <option value="">-- Seleccione --</option>
        <?php foreach ($provincias as $provincia): ?>
          <?php $pid = (int)($provincia['id'] ?? 0); ?>
          <option value="<?= $pid ?>" <?= $pid === $provSel ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($provincia['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Cantón
      <select id="canton_id" name="canton_id" class="select">
        <option value="">-- Seleccione --</option>
        <?php foreach ($cantones as $canton): ?>
          <?php $cid = (int)($canton['id'] ?? 0); ?>
          <option value="<?= $cid ?>" <?= $cid === $cantSel ? 'selected' : '' ?>>
            <?= htmlspecialchars((string)($canton['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="col-span-2">
      Tipo de entidad
      <select id="tipo_entidad" name="tipo_entidad" class="select">
        <?php foreach ($tiposEntidad as $tipo): ?>
          <option value="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>" <?= $tipo === $tipoActual ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $tipo)), ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>

  <label class="col-span-2">
    Segmento (solo cooperativa)
    <select name="id_segmento">
      <?php foreach ($segmentOptions as $valor => $label): ?>
        <option value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>" <?= $segmentoActual === (string)$valor ? 'selected' : '' ?>>
          <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <div class="col-span-2 field-stack">
    <span>Servicios</span>
    <div class="chips" role="group" aria-label="Servicios disponibles">
      <?php foreach ($servicios as $servicio): ?>
        <?php
          $sid   = (int)($servicio['id'] ?? $servicio['id_servicio'] ?? 0);
          $sname = (string)($servicio['nombre'] ?? $servicio['nombre_servicio'] ?? '');
          $checked = in_array($sid, $servSel, true);
        ?>
        <label class="chip<?= $checked ? ' is-checked' : '' ?>">
          <input type="checkbox" name="servicios[]" value="<?= $sid ?>" <?= $checked ? 'checked' : '' ?>>
          <span><?= htmlspecialchars($sname, ENT_QUOTES, 'UTF-8') ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <label class="col-span-2">
    Notas
    <textarea name="notas" rows="5" placeholder="Observaciones..."><?= htmlspecialchars((string)$val('notas'), ENT_QUOTES, 'UTF-8') ?></textarea>
  </label>

  <?php if ($isCreate): ?>
    <input type="hidden" name="telefono" value="">
  <?php endif; ?>
</div>

<script>
(function(){
  const provinciaSelect = document.getElementById('provincia_id');
  const cantonSelect = document.getElementById('canton_id');
  if (!provinciaSelect || !cantonSelect) return;
  provinciaSelect.addEventListener('change', function(){
    const pid = this.value;
    const url = this.getAttribute('data-cantones-url');
    if (!pid || !url) {
      cantonSelect.innerHTML = '<option value="">-- Seleccione --</option>';
      return;
    }
    fetch(url + '?provincia_id=' + encodeURIComponent(pid))
      .then(function(resp){ return resp.ok ? resp.json() : []; })
      .then(function(data){
        cantonSelect.innerHTML = '<option value="">-- Seleccione --</option>';
        if (!Array.isArray(data)) return;
        data.forEach(function(canton){
          if (!canton || typeof canton.id === 'undefined') return;
          var option = document.createElement('option');
          option.value = canton.id;
          option.textContent = canton.nombre || ('Cantón ' + canton.id);
          cantonSelect.appendChild(option);
        });
      })
      .catch(function(){
        cantonSelect.innerHTML = '<option value="">-- Seleccione --</option>';
      });
  });
})();
</script>
