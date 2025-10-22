<?php

/** Plantilla de Formulario Entidad (crear/editar) */

$errors = is_array($errors ?? null) ? $errors : [];
$item   = is_array($item ?? null) ? $item : [];
$old    = is_array($old ?? null) ? $old : [];

$provincias = is_array($provincias ?? null) ? $provincias : [];
$cantones   = is_array($cantones ?? null) ? $cantones : [];
$servicios  = is_array($servicios ?? null) ? $servicios : [];
$segmentosData = is_array($segmentos ?? null) ? $segmentos : [];

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
    $sid = (string)($segmento['id_segmento'] ?? $segmento['id'] ?? '');
    $sname = (string)($segmento['nombre_segmento'] ?? $segmento['nombre'] ?? '');
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
$segmentoVisible = $tipoActual === 'cooperativa';
$emailHasError   = isset($errors['email']);
$emailCssClass   = $emailHasError ? 'is-invalid' : '';
$direccionCalleError = isset($errors['direccion_calle']) ? (string)$errors['direccion_calle'] : null;
$direccionInterError = isset($errors['direccion_interseccion']) ? (string)$errors['direccion_interseccion'] : null;
$logoError         = isset($errors['logo']) ? (string)$errors['logo'] : null;
$logoPath          = null;
$isEditMode        = !empty($item);

$logoOld = isset($old['logo_path']) ? trim((string)$old['logo_path']) : '';
if ($logoOld !== '') {
  $logoPath = $logoOld;
}

if ($logoPath === null && isset($item['logo_path'])) {
  $logoItem = trim((string)$item['logo_path']);
  if ($logoItem !== '') {
    $logoPath = $logoItem;
  }
}

$logoUrl = $logoPath !== null ? '/storage/' . ltrim((string)$logoPath, '/') : null;
$showExistingLogo = $logoUrl !== null;
?>

<div class="ent-form__grid grid grid-2">
  <label class="col-span-2">
    Nombre * <?= isset($errors['nombre']) ? '<small class="text-error">' . $errors['nombre'] . '</small>' : '' ?>
    <input
      type="text"
      name="nombre"
      required
      placeholder="Ej.: COAC del Ecuador"
      value="<?= htmlspecialchars((string)$val('nombre'), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <label class="col-span-2">
    Cédula / RUC (10–13) <?= isset($errors['ruc']) ? '<small class="text-error">' . $errors['ruc'] . '</small>' : '' ?>
    <input
      type="text"
      name="nit"
      inputmode="numeric"
      pattern="^\d{10,13}$"
      minlength="10"
      maxlength="13"
      title="Solo números, entre 10 y 13 dígitos"
      placeholder="Ej.: 1712345678 o 1790012345001"
      value="<?= htmlspecialchars((string)$val('nit', $val('ruc')), ENT_QUOTES, 'UTF-8') ?>">
  </label>

  <div class="col-span-2 ent-logo-field">
    <label class="ent-logo-label" for="ent-logo-input">
      Imagen de la entidad
      <?= $logoError !== null ? '<small class="text-error">' . htmlspecialchars($logoError, ENT_QUOTES, 'UTF-8') . '</small>' : '' ?>
    </label>
    <div class="ent-logo-field__body">
      <div class="ent-logo-field__controls">
        <input
          type="file"
          id="ent-logo-input"
          name="logo"
          accept="image/png,image/jpeg,image/webp,image/gif"
          data-logo-input>
        <p class="ent-logo-hint">Se adaptará automáticamente al recuadro visual.</p>
      </div>
      <div class="ent-logo-preview" data-logo-preview<?php if ($logoUrl !== null): ?> data-original-src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
        <span class="ent-logo-preview__title">Vista previa</span>
        <div class="ent-logo-preview__frame">
          <img
            alt="Vista previa del logo"
            data-logo-image
            <?php if ($showExistingLogo): ?>
              src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>"
            <?php else: ?>
              src=""
              hidden
            <?php endif; ?>>
          <span class="ent-logo-preview__placeholder" data-logo-placeholder<?php if ($showExistingLogo): ?> hidden<?php endif; ?>>Sin imagen seleccionada</span>
        </div>
      </div>
    </div>
  </div>

  <div class="grid-2 col-span-2 ent-form__row">
    <label>
      Teléfono fijo <?= isset($errors['telefono_fijo']) ? '<small class="text-error">' . $errors['telefono_fijo'] . '</small>' : '' ?>
      <input
        type="text"
        name="telefono_fijo"
        inputmode="numeric"
        pattern="^\d{7}$"
        minlength="7"
        maxlength="7"
        title="Solo números, 7 dígitos"
        placeholder="Ej.: 022345678"
        value="<?= htmlspecialchars((string)$val('telefono_fijo', $val('telefono_fijo_1')), ENT_QUOTES, 'UTF-8') ?>">
    </label>
    <label>
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
  </div>
  <label class="col-span-2">
    Correo electrónico (opcional) <?= $emailHasError ? '<small class="text-error">' . $errors['email'] . '</small>' : '' ?>
    <input
      type="email"
      name="email"
      required
      placeholder="Ej.: contacto@coac.ec"
      value="<?= htmlspecialchars((string)$val('email'), ENT_QUOTES, 'UTF-8') ?>"
      class="<?= $emailCssClass ?>"
      aria-invalid="<?= $emailHasError ? 'true' : 'false' ?>">
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

    <label>
      Calle principal
      <?php if ($direccionCalleError !== null): ?><small class="text-error"><?= htmlspecialchars($direccionCalleError, ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
      <input
        type="text"
        name="direccion_calle"
        maxlength="255"
        placeholder="Ej.: Av. Amazonas"
        value="<?= htmlspecialchars((string)$val('direccion_calle'), ENT_QUOTES, 'UTF-8') ?>">
    </label>

    <label>
      Intersección o referencia
      <?php if ($direccionInterError !== null): ?><small class="text-error"><?= htmlspecialchars($direccionInterError, ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
      <input
        type="text"
        name="direccion_interseccion"
        maxlength="255"
        placeholder="Ej.: y Juan León Mera"
        value="<?= htmlspecialchars((string)$val('direccion_interseccion'), ENT_QUOTES, 'UTF-8') ?>">
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

  <label class="col-span-2" id="segmento_wrap"<?= $segmentoVisible ? '' : ' style="display:none;"' ?>>
    Segmento (solo para entidades tipo cooperativa)
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
</div>

<script>
  (function() {
    var script = document.currentScript || null;
    if (!script) {
      return;
    }

    var form = script.closest('form');
    if (!form) {
      return;
    }

    var onlyDigits = function(event) {
      var element = event.target;
      var max = element.getAttribute('maxlength');
      var value = element.value.replace(/\D+/g, '');

      if (max && /^\d+$/.test(max)) {
        value = value.slice(0, parseInt(max, 10));
      }

      if (element.value !== value) {
        var pos = element.selectionStart;
        element.value = value;
        if (pos !== null && pos !== undefined) {
          var caret = Math.min(pos - 1, element.value.length);
          element.setSelectionRange(caret, caret);
        }
      }
    };

    var selectors = [
      'input[name="nit"]',
      'input[name="telefono_fijo"]',
      'input[name="telefono_movil"]'
    ];

    var inputs = form.querySelectorAll(selectors.join(','));
    inputs.forEach(function(element) {
      element.setAttribute('inputmode', 'numeric');
      element.setAttribute('autocomplete', 'off');

      element.addEventListener('input', onlyDigits);
      element.addEventListener('paste', function(event) {
        event.preventDefault();
        var text = (event.clipboardData || window.clipboardData).getData('text') || '';
        var clean = text.replace(/\D+/g, '');
        if (document.execCommand) {
          document.execCommand('insertText', false, clean);
        } else {
          var start = element.selectionStart || 0;
          var end = element.selectionEnd || 0;
          var current = element.value;
          element.value = current.slice(0, start) + clean + current.slice(end);
        }
      });
    });

    var allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
    inputs.forEach(function(element) {
      element.addEventListener('keydown', function(event) {
        if (allowed.indexOf(event.key) !== -1) {
          return;
        }

        if ((event.ctrlKey || event.metaKey) && ['a', 'c', 'v', 'x'].indexOf(event.key.toLowerCase()) !== -1) {
          return;
        }

        if (/^\d$/.test(event.key)) {
          return;
        }

        event.preventDefault();
      });
    });

    var logoInput = form.querySelector('[data-logo-input]');
    var logoPreview = form.querySelector('[data-logo-preview]');
    var previewImg = logoPreview ? logoPreview.querySelector('[data-logo-image]') : null;
    var previewPlaceholder = logoPreview ? logoPreview.querySelector('[data-logo-placeholder]') : null;
    var originalSrc = logoPreview ? (logoPreview.getAttribute('data-original-src') || '') : '';
    var activeFileSrc = '';

    var showPreview = function(src) {
      if (!logoPreview || !previewImg) { return; }
      if (typeof src === 'string' && src !== '') {
        previewImg.src = src;
      }
      previewImg.removeAttribute('hidden');
      if (previewPlaceholder) {
        previewPlaceholder.setAttribute('hidden', 'true');
      }
    };

    var showPlaceholder = function() {
      if (!logoPreview) { return; }
      if (previewImg) {
        previewImg.setAttribute('hidden', 'true');
        previewImg.src = '';
      }
      if (previewPlaceholder) {
        previewPlaceholder.removeAttribute('hidden');
      }
    };

    var restoreOriginalIfNecessary = function() {
      if (!logoPreview) { return; }
      if (activeFileSrc) {
        showPreview(activeFileSrc);
      } else if (originalSrc) {
        showPreview(originalSrc);
      } else {
        showPlaceholder();
      }
    };

    if (logoInput && logoPreview) {
      logoInput.addEventListener('change', function() {
        var file = logoInput.files && logoInput.files[0] ? logoInput.files[0] : null;
        if (!file) {
          activeFileSrc = '';
          restoreOriginalIfNecessary();
          return;
        }

        if (typeof FileReader === 'undefined') {
          return;
        }

        var reader = new FileReader();
        reader.onload = function(event) {
          var result = event && event.target ? event.target.result : null;
          if (typeof result === 'string') {
            activeFileSrc = result;
            showPreview(result);
          }
        };
        reader.readAsDataURL(file);

      });
    }

    if (logoPreview) {
      if (originalSrc) {
        showPreview(originalSrc);
      } else {
        showPlaceholder();
      }
    }
  })();
</script>
