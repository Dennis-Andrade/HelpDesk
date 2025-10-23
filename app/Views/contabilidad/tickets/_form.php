<?php
if (!function_exists('ctk_h')) {
    function ctk_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$errors        = is_array($errors ?? null) ? $errors : [];
$old           = is_array($old ?? null) ? $old : [];
$item          = is_array($item ?? null) ? $item : [];
$cooperativas  = is_array($cooperativas ?? null) ? $cooperativas : [];
$categorias    = is_array($categorias ?? null) ? $categorias : [];
$prioridades   = is_array($prioridades ?? null) ? $prioridades : [];
$estados       = is_array($estados ?? null) ? $estados : [];

$value = static function (string $key, $default = null) use ($old, $item) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    if (array_key_exists($key, $item)) {
        return $item[$key];
    }
    return $default;
};

$selectedEntidad = (int)$value('id_cooperativa', 0);
$selectedContrato = (int)$value('id_contratacion', 0);
$selectedCategoria = (string)$value('categoria', '');
$selectedPrioridad = (string)$value('prioridad', '');
$selectedEstado = (string)$value('estado', 'Nuevo');
?>
<div class="form-grid">
  <div class="form-field form-field--full">
    <label for="ticket-entidad">Entidad *</label>
    <select id="ticket-entidad" name="id_cooperativa" required class="select">
      <option value="">Seleccione</option>
      <?php foreach ($cooperativas as $entidad): ?>
        <?php $id = (int)($entidad['id'] ?? 0); ?>
        <option value="<?= $id ?>" <?= $id === $selectedEntidad ? 'selected' : '' ?>>
          <?= ctk_h($entidad['nombre'] ?? '') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field">
    <label for="ticket-contrato">Contrato asociado</label>
    <input
      id="ticket-contrato"
      type="number"
      name="id_contratacion"
      min="1"
      value="<?= ctk_h((string)$selectedContrato) ?>"
      placeholder="ID del contrato (opcional)">
    <span class="form-hint">Si el ticket está ligado a un contrato, ingresa su ID.</span>
  </div>

  <div class="form-field">
    <label for="ticket-prioridad">Prioridad *</label>
    <select id="ticket-prioridad" name="prioridad" required class="select">
      <option value="">Seleccione</option>
      <?php foreach ($prioridades as $prioridad): ?>
        <?php $nombre = (string)$prioridad; ?>
        <option value="<?= ctk_h($nombre) ?>" <?= strcasecmp($nombre, $selectedPrioridad) === 0 ? 'selected' : '' ?>>
          <?= ctk_h($nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field">
    <label for="ticket-estado">Estado *</label>
    <select id="ticket-estado" name="estado" required class="select">
      <?php foreach ($estados as $estado): ?>
        <?php $nombre = (string)$estado; ?>
        <option value="<?= ctk_h($nombre) ?>" <?= strcasecmp($nombre, $selectedEstado) === 0 ? 'selected' : '' ?>>
          <?= ctk_h($nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field form-field--full">
    <label for="ticket-categoria">Categoría *</label>
    <select id="ticket-categoria" name="categoria" required class="select">
      <option value="">Seleccione</option>
      <?php foreach ($categorias as $categoria): ?>
        <?php $nombre = (string)$categoria; ?>
        <option value="<?= ctk_h($nombre) ?>" <?= strcasecmp($nombre, $selectedCategoria) === 0 ? 'selected' : '' ?>>
          <?= ctk_h($nombre) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-field form-field--full">
    <label for="ticket-asunto">Asunto *</label>
    <input
      id="ticket-asunto"
      type="text"
      name="asunto"
      required
      maxlength="150"
      value="<?= ctk_h((string)$value('asunto', '')) ?>"
      placeholder="Ej.: Solicitud de nota de crédito">
  </div>

  <div class="form-field form-field--full">
    <label for="ticket-descripcion">Descripción *</label>
    <textarea
      id="ticket-descripcion"
      name="descripcion"
      rows="5"
      required
      placeholder="Describe el caso, adjunta números de comprobantes, fechas y compromisos."><?= ctk_h((string)$value('descripcion', '')) ?></textarea>
  </div>

  <div class="form-field form-field--full">
    <label for="ticket-observaciones">Observaciones</label>
    <textarea
      id="ticket-observaciones"
      name="observaciones"
      rows="3"
      placeholder="Notas internas, responsables, próximos pasos."><?= ctk_h((string)$value('observaciones', '')) ?></textarea>
  </div>
</div>
