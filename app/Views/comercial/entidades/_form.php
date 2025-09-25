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

    switch ($key) {
        case 'nit':
            if (isset($item['ruc'])) {
                return $item['ruc'];
            }
            break;
        case 'telefono_fijo':
            if (isset($item['telefono_fijo_1'])) {
                return $item['telefono_fijo_1'];
            }
            if (isset($item['telefono'])) {
                return $item['telefono'];
            }
            break;
        case 'telefono_movil':
            if (isset($item['telefono_movil'])) {
                return $item['telefono_movil'];
            }
            break;
        case 'id_segmento':
            if (isset($item['segmento'])) {
                return $item['segmento'];
            }
            break;
        case 'tipo_entidad':
            if (isset($item['tipo'])) {
                return $item['tipo'];
            }
            break;
    }

    return $default;
};

$provSel = (int)$val('provincia_id', 0);
$cantSel = (int)$val('canton_id', 0);

$tipoEntidad = (string)$val('tipo_entidad', 'cooperativa');
if ($tipoEntidad === '') {
    $tipoEntidad = 'cooperativa';
}

$tipoEntidadOptions = array(
    'cooperativa'          => 'Cooperativa',
    'mutualista'           => 'Mutualista',
    'sujeto_no_financiero' => 'Sujeto no financiero',
    'caja_ahorros'         => 'Caja de ahorros',
    'casa_valores'         => 'Casa de valores',
);

$serviciosSeleccionados = array();
if (isset($old['servicios']) && is_array($old['servicios'])) {
    $serviciosSeleccionados = $old['servicios'];
} elseif (isset($item['servicios']) && is_array($item['servicios'])) {
    $serviciosSeleccionados = $item['servicios'];
}

$serviciosSeleccionados = array_values(array_filter(array_map(static function ($value) {
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (is_string($value) && $value !== '' && is_numeric($value)) {
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }
    return null;
}, $serviciosSeleccionados), static function ($value) {
    return $value !== null;
}));

if (empty($serviciosSeleccionados)) {
    $serviciosSeleccionados = array('');
}
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
    <label for="ent-nit">RUC / Cédula</label>
    <input id="ent-nit" name="nit" type="text" inputmode="numeric" pattern="^\d{10,13}$" maxlength="13" value="<?= htmlspecialchars((string)$val('nit'), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['ruc'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['ruc'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-telefono-fijo">Teléfono fijo</label>
    <input id="ent-telefono-fijo" name="telefono_fijo" type="text" inputmode="numeric" pattern="^\d{7}$" maxlength="7" value="<?= htmlspecialchars((string)$val('telefono_fijo'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0234567">
    <?php if (isset($errors['telefono_fijo'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['telefono_fijo'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-telefono-movil">Teléfono móvil</label>
    <input id="ent-telefono-movil" name="telefono_movil" type="text" inputmode="numeric" pattern="^\d{10}$" maxlength="10" value="<?= htmlspecialchars((string)$val('telefono_movil'), ENT_QUOTES, 'UTF-8') ?>" placeholder="0991234567">
    <?php if (isset($errors['telefono_movil'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['telefono_movil'], ENT_QUOTES, 'UTF-8') ?></p>
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
    <label for="ent-tipo-entidad">Tipo de entidad</label>
    <select id="ent-tipo-entidad" name="tipo_entidad">
      <?php foreach ($tipoEntidadOptions as $value => $label): ?>
        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $value === $tipoEntidad ? 'selected' : '' ?>>
          <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="ent-form__row">
    <label for="ent-segmento">Segmento (ID)</label>
    <input id="ent-segmento" name="id_segmento" type="number" inputmode="numeric" min="1" value="<?= htmlspecialchars((string)$val('id_segmento'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej.: 3">
    <?php if (isset($errors['segmento'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['segmento'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row">
    <label for="ent-notas">Notas</label>
    <textarea id="ent-notas" name="notas" rows="3"><?= htmlspecialchars((string)$val('notas'), ENT_QUOTES, 'UTF-8') ?></textarea>
    <?php if (isset($errors['notas'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['notas'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>

  <div class="ent-form__row ent-form__row--servicios">
    <label id="ent-servicios-label" for="ent-servicio-0">Servicios (IDs)</label>
    <div id="ent-servicios-fields" class="ent-servicios-fields" aria-describedby="ent-servicios-help">
      <?php foreach ($serviciosSeleccionados as $index => $servicioId): ?>
        <div class="ent-servicios-fields__item">
          <input
            id="ent-servicio-<?= $index ?>"
            name="servicios[]"
            type="number"
            inputmode="numeric"
            min="1"
            value="<?= $servicioId === '' ? '' : htmlspecialchars((string)$servicioId, ENT_QUOTES, 'UTF-8') ?>"
            aria-labelledby="ent-servicios-label ent-servicios-help"
          >
        </div>
      <?php endforeach; ?>
    </div>
    <p id="ent-servicios-help" class="field-hint">Ingresa los identificadores numéricos de los servicios a asociar.</p>
    <button type="button" class="btn btn-outline ent-servicios-add" data-add-servicio>Agregar servicio</button>
    <?php if (isset($errors['servicios'])): ?>
      <p class="field-error" role="alert"><?= htmlspecialchars($errors['servicios'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
  </div>
</div>

<script>
(function() {
  var provincia = document.getElementById('ent-provincia');
  var canton = document.getElementById('ent-canton');
  if (provincia && canton) {
    var url = provincia.getAttribute('data-cantones-url') || '';
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
  }

  var serviciosContainer = document.getElementById('ent-servicios-fields');
  var addServicioButton = document.querySelector('[data-add-servicio]');
  if (serviciosContainer && addServicioButton) {
    addServicioButton.addEventListener('click', function (event) {
      event.preventDefault();
      var index = serviciosContainer.querySelectorAll('input').length;
      var wrapper = document.createElement('div');
      wrapper.className = 'ent-servicios-fields__item';
      var input = document.createElement('input');
      input.type = 'number';
      input.name = 'servicios[]';
      input.min = '1';
      input.inputMode = 'numeric';
      input.id = 'ent-servicio-' + index;
      input.setAttribute('aria-labelledby', 'ent-servicios-label ent-servicios-help');
      wrapper.appendChild(input);
      serviciosContainer.appendChild(wrapper);
      input.focus();
    });
  }
})();
</script>
