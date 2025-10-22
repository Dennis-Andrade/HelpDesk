<?php
if (!function_exists('cseg_h')) {
    function cseg_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$errors        = is_array($errors ?? null) ? $errors : [];
$old           = is_array($old ?? null) ? $old : [];
$cooperativas  = is_array($cooperativas ?? null) ? $cooperativas : [];
$tipos         = is_array($tipos ?? null) ? $tipos : [];
$medios        = is_array($medios ?? null) ? $medios : [];
$resultados    = is_array($resultados ?? null) ? $resultados : [];

$value = static function (string $key, $default = null) use ($old) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    return $default;
};

function cseg_error(string $key, array $errors): ?string
{
    foreach ($errors as $message) {
        if (stripos($message, $key) !== false) {
            return $message;
        }
    }
    return null;
}

$selectedEntidad = (int)$value('id_cooperativa', 0);
$selectedContrato = (int)$value('id_contratacion', 0);
$selectedMedio = (string)$value('medio', '');
$selectedResultado = (string)$value('resultado', '');
$selectedTipo = (string)$value('tipo', '');
$selectedContacto = (int)$value('id_contacto', 0);
$ticketCodigo = (string)$value('ticket_codigo', '');
$ticketId = (int)$value('ticket_id', 0);
?>

<div class="form-grid">
  <div class="form-field">
    <label for="seguimiento-entidad">Entidad *</label>
    <select
      id="seguimiento-entidad"
      name="id_cooperativa"
      required
      class="select"
      data-contactos-url="/contabilidad/seguimiento/contactos"
      data-contratos-url="/contabilidad/seguimiento/contratos">
      <option value="">Seleccione</option>
      <?php foreach ($cooperativas as $entidad): ?>
        <?php $id = (int)($entidad['id'] ?? 0); ?>
        <option value="<?= $id ?>" <?= $id === $selectedEntidad ? 'selected' : '' ?>>
          <?= cseg_h($entidad['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field">
    <label for="seguimiento-contrato">Contrato</label>
    <select id="seguimiento-contrato" name="id_contratacion" class="select" data-contrato-select data-contratos-url="/contabilidad/seguimiento/contratos">
      <option value="">Sin contrato</option>
      <?php if ($selectedContrato > 0): ?>
        <option value="<?= $selectedContrato ?>" selected data-loaded="1">
          <?= cseg_h((string)$value('contrato_codigo', 'Contrato #' . $selectedContrato)) ?>
        </option>
      <?php endif; ?>
    </select>
  </div>

  <div class="form-field">
    <label for="seguimiento-fecha-inicio">Fecha de gestión *</label>
    <input
      id="seguimiento-fecha-inicio"
      type="date"
      name="fecha_inicio"
      required
      value="<?= cseg_h((string)$value('fecha_inicio', date('Y-m-d'))) ?>">
  </div>

  <div class="form-field">
    <label for="seguimiento-fecha-fin">Fecha de cierre</label>
    <input
      id="seguimiento-fecha-fin"
      type="date"
      name="fecha_fin"
      value="<?= cseg_h((string)$value('fecha_fin', '')) ?>">
  </div>

  <div class="form-field">
    <label for="seguimiento-tipo">Tipo de gestión *</label>
    <select id="seguimiento-tipo" name="tipo" required class="select" data-seguimiento-tipo>
      <option value="">Seleccione</option>
      <?php foreach ($tipos as $tipo): ?>
        <?php $nombre = (string)$tipo; ?>
        <option value="<?= cseg_h($nombre) ?>" <?= strcasecmp($nombre, $selectedTipo) === 0 ? 'selected' : '' ?>>
          <?= cseg_h($nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field">
    <label for="seguimiento-medio">Medio</label>
    <select id="seguimiento-medio" name="medio" class="select">
      <option value="">Seleccione</option>
      <?php foreach ($medios as $medio): ?>
        <?php $nombre = (string)$medio; ?>
        <option value="<?= cseg_h($nombre) ?>" <?= strcasecmp($nombre, $selectedMedio) === 0 ? 'selected' : '' ?>>
          <?= cseg_h($nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field">
    <label for="seguimiento-resultado">Resultado</label>
    <select id="seguimiento-resultado" name="resultado" class="select">
      <option value="">Seleccione</option>
      <?php foreach ($resultados as $resultado): ?>
        <?php $nombre = (string)$resultado; ?>
        <option value="<?= cseg_h($nombre) ?>" <?= strcasecmp($nombre, $selectedResultado) === 0 ? 'selected' : '' ?>>
          <?= cseg_h($nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field form-field--full" data-contacto-wrapper hidden>
    <label for="seguimiento-contacto">Contacto relacionado</label>
    <select id="seguimiento-contacto" name="id_contacto" class="select" data-contacto-select>
      <option value="">Seleccione</option>
      <?php if ($selectedContacto > 0): ?>
        <option value="<?= $selectedContacto ?>" selected data-loaded="1"><?= cseg_h((string)$value('contacto_nombre', 'Contacto')) ?></option>
      <?php endif; ?>
    </select>
  </div>

  <div class="form-field form-field--full" data-ticket-wrapper hidden>
    <label for="seguimiento-ticket">Ticket contable</label>
    <div class="seguimiento-ticket-field">
      <input
        id="seguimiento-ticket"
        type="text"
        placeholder="Buscar por código o asunto"
        autocomplete="off"
        value="<?= cseg_h($ticketCodigo) ?>"
        data-ticket-input>
      <input type="hidden" name="ticket_id" value="<?= $ticketId ?>" data-ticket-id>
      <button type="button" class="btn btn-sm btn-outline" data-ticket-buscar>
        <span class="material-symbols-outlined" aria-hidden="true">search</span>
        Buscar
      </button>
    </div>
    <p class="form-hint">Escribe al menos 3 caracteres para buscar tickets registrados.</p>
    <div class="seguimiento-ticket-preview" data-ticket-preview hidden></div>
  </div>

  <div class="form-field form-field--full">
    <label for="seguimiento-descripcion">Detalle *</label>
    <textarea
      id="seguimiento-descripcion"
      name="descripcion"
      rows="5"
      required
      placeholder="Describe la gestión realizada, acuerdos y compromisos."><?= cseg_h((string)$value('descripcion', '')) ?></textarea>
  </div>
</div>
