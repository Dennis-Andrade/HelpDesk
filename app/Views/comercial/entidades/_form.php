<?php
/** Plantilla de Formulario Entidad (crear/editar) */

// Defaults seguros para evitar Notice/Undefined
$provSel = (int)($item['provincia_id'] ?? $old['provincia_id'] ?? 0);
$cantSel = (int)($item['canton_id']   ?? $old['canton_id']   ?? 0);
$title   = $title   ?? 'Cooperativa';
$action  = $action  ?? '/comercial/entidades/crear';
$csrf    = $csrf    ?? '';
$errors  = is_array($errors ?? null) ? $errors : [];
$item    = is_array($item ?? null)   ? $item   : [];
$old     = is_array($old ?? null)    ? $old    : [];

// Catálogos opcionales
$provincias = is_array($provincias ?? null) ? $provincias : [];
$cantones   = is_array($cantones   ?? null) ? $cantones   : [];
$servicios  = is_array($servicios  ?? null) ? $servicios  : [];

// Helper para tomar valor (prioriza old, luego item)
$val = static function(string $k, $def='') use ($old, $item) {
    if (array_key_exists($k, $old))  return $old[$k];
    if (array_key_exists($k, $item)) return $item[$k];
    return $def;
};

// Servicios seleccionados (cuando edita o postea)
$servSel = $val('servicios', []);
if (!is_array($servSel)) $servSel = [];
$servSel = array_map('intval', $servSel);
?>
<section class="card ent-form">
  <h1><?= htmlspecialchars($title ?: 'Cooperativa') ?></h1>

  <form method="post" action="<?= htmlspecialchars($action) ?>" class="form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="grid grid-2">

      <!-- Nombre -->
      <label class="col-span-2">
        Nombre * <?= isset($errors['nombre']) ? '<small class="text-error">'.$errors['nombre'].'</small>' : '' ?>
        <input
          type="text"
          name="nombre"
          required
          placeholder="Ej.: COAC SAN JUAN LTDA"
          value="<?= htmlspecialchars($val('nombre')) ?>">
      </label>

      <!-- Cédula / RUC -->
      <label>
        Cédula / RUC (10–13) <?= isset($errors['ruc']) ? '<small class="text-error">'.$errors['ruc'].'</small>' : '' ?>
        <input
          type="text"
          name="nit"
          inputmode="numeric"
          pattern="^\d{10,13}$"
          minlength="10"
          maxlength="13"
          title="Solo números, entre 10 y 13 dígitos"
          placeholder="Ej.: 1712345678 o 1790012345001"
          value="<?= htmlspecialchars($val('nit', $val('ruc'))) ?>">
      </label>

      <!-- Teléfono fijo -->
      <label>
        Teléfono fijo <?= isset($errors['telefono_fijo']) ? '<small class="text-error">'.$errors['telefono_fijo'].'</small>' : '' ?>
        <input
          type="text"
          name="telefono_fijo"
          inputmode="numeric"
          pattern="^\d{7}$"
          minlength="7"
          maxlength="7"
          title="Solo números, 7 dígitos"
          placeholder="Ej.: 022345678"
          value="<?= htmlspecialchars($val('telefono_fijo')) ?>">
      </label>

      <!-- Celular -->
      <label class="col-span-2">
        Celular <?= isset($errors['telefono_movil']) ? '<small class="text-error">'.$errors['telefono_movil'].'</small>' : '' ?>
        <input
          type="text"
          name="telefono_movil"
          inputmode="numeric"
          pattern="^\d{10}$"
          minlength="10"
          maxlength="10"
          title="Solo números, 10 dígitos"
          placeholder="Ej.: 0998765432"
          value="<?= htmlspecialchars($val('telefono_movil')) ?>">
      </label>

      <!-- Email -->
      <label class="col-span-2">
        Email <?= isset($errors['email']) ? '<small class="text-error">'.$errors['email'].'</small>' : '' ?>
        <input
          type="email"
          name="email"
          placeholder="ejemplo@dominio.com"
          value="<?= htmlspecialchars($val('email')) ?>">
      </label>

      <div class="grid-2">
        <!-- Provincia -->
        <label>
          Provincia
          <select id="provincia_id"
                  name="provincia_id"
                  class="select"
                  data-cantones-url="/shared/cantones">
            <option value="">-- Seleccione --</option>
            <?php foreach (($provincias ?? []) as $p): ?>
              <option value="<?= (int)$p['id'] ?>"
                      <?= ((int)($item['provincia_id'] ?? $old['provincia_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <!-- Cantón -->
        <label>
          Cantón
          <select id="canton_id"
                  name="canton_id"
                  class="select">
            <option value="">-- Seleccione --</option>
            <?php if (!empty($cantones)): ?>
              <?php foreach ($cantones as $c): ?>
                <option value="<?= (int)$c['id'] ?>"
                        <?= ((int)($item['canton_id'] ?? $old['canton_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </label>

        <!-- Tipo de entidad (nueva fila, ocupa 2 columnas) -->
        <label class="col-span-2">
          Tipo de entidad
          <select id="tipo_entidad" name="tipo_entidad" class="select">
            <?php
            // valores permitidos
            $tipos = ['cooperativa','mutualista','sujeto_no_financiero','caja_ahorros','casa_valores'];
            $tipoSel = ($item['tipo_entidad'] ?? $old['tipo_entidad'] ?? 'cooperativa');
            ?>
            <?php foreach ($tipos as $t): ?>
              <option value="<?= $t ?>" <?= $t===$tipoSel ? 'selected' : '' ?>>
                <?= ucfirst(str_replace('_',' ', $t)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>

      <!-- Segmento (solo cooperativa) -->
      <label class="col-span-2">
        Segmento (solo cooperativa)
        <select name="id_segmento">
          <?php
            $seg = (string)$val('id_segmento', '');
            $segOpts = ['' => '-- N/A --', '1'=>'Segmento 1','2'=>'Segmento 2','3'=>'Segmento 3','4'=>'Segmento 4','5'=>'Segmento 5'];
            foreach ($segOpts as $v=>$label):
          ?>
            <option value="<?= htmlspecialchars($v) ?>" <?= $seg===(string)$v?'selected':'' ?>>
              <?= htmlspecialchars($label) ?>
            </option>
          <?php endforeach ?>
        </select>
      </label>

    </div>

    <!-- Servicios -->
    <label class="col-span-2">
      Servicios
      <div class="chips">
        <?php foreach ($servicios as $s):
          // Acepta diferentes nombres de campos (id vs id_servicio, nombre vs nombre_servicio)
          $sid   = (int)($s['id'] ?? $s['id_servicio'] ?? 0);
          $sname = (string)($s['nombre'] ?? $s['nombre_servicio'] ?? '');
          $checked = in_array($sid, $servSel, true);
        ?>
          <label class="chip<?= $checked ? ' is-checked':'' ?>">
            <input type="checkbox" name="servicios[]" value="<?= $sid ?>" <?= $checked?'checked':'' ?>>
            <span><?= htmlspecialchars($sname) ?></span>
          </label>
        <?php endforeach ?>
      </div>
    </label>

    <!-- Notas -->
    <label class="col-span-2">
      Notas
      <textarea name="notas" rows="5" placeholder="Observaciones..."><?= htmlspecialchars($val('notas')) ?></textarea>
    </label>
  </form>
</section>
<script>
/**
 * Restringe a dígitos en tiempo real
 * - Limpia cualquier caracter no numérico al escribir/pegar.
 * - Respeta longitud máxima si el input la define (maxlength).
 */
(function () {
  const onlyDigits = (ev) => {
    const el = ev.target;
    const max = el.getAttribute('maxlength');
    // Solo dígitos
    let val = el.value.replace(/\D+/g, '');
    // Si hay maxlength, recorta
    if (max && /^\d+$/.test(max)) {
      val = val.slice(0, parseInt(max, 10));
    }
    if (el.value !== val) {
      const pos = el.selectionStart;
      el.value = val;
      // intenta mantener la posición del cursor
      if (pos !== null) el.setSelectionRange(Math.min(pos - 1, el.value.length), Math.min(pos - 1, el.value.length));
    }
  };

  // Selecciona tus campos numéricos del formulario
  const selectors = [
    'input[name="nit"]',            // cédula / RUC (10–13)
    'input[name="telefono_fijo"]',  // fijo (7)
    'input[name="telefono_movil"]'  // celular (10)
  ];
  const inputs = document.querySelectorAll(selectors.join(','));
  inputs.forEach((el) => {
    // Refuerzo de hints para teclado móvil
    el.setAttribute('inputmode', 'numeric');
    el.setAttribute('autocomplete', 'off');

    el.addEventListener('input', onlyDigits);
    el.addEventListener('paste', function (e) {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text') || '';
      const clean = text.replace(/\D+/g, '');
      document.execCommand('insertText', false, clean);
    });
  });

  // Opcional: bloquear teclas no numéricas (salvo navegación/borrar/tab)
  const allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
  inputs.forEach((el) => el.addEventListener('keydown', (e) => {
    if (allowed.includes(e.key)) return;
    // Permitir atajos (Ctrl/Meta + C/V/X/A)
    if ((e.ctrlKey || e.metaKey) && ['a','c','v','x'].includes(e.key.toLowerCase())) return;
    // Permitir números (fila superior y numpad)
    if (/^\d$/.test(e.key)) return;
    e.preventDefault();
  }));
})();
</script>
