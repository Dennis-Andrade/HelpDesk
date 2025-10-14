<?php
if (!function_exists('seguimiento_h')) {
    function seguimiento_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$crumbs       = isset($crumbs) && is_array($crumbs) ? $crumbs : [];
$cooperativas = isset($cooperativas) && is_array($cooperativas) ? $cooperativas : [];
$tipos        = isset($tipos) && is_array($tipos) ? $tipos : [];
$action       = '/comercial/eventos';
$defaultFecha = isset($defaultFecha) && $defaultFecha !== '' ? (string)$defaultFecha : date('Y-m-d');
$defaultTipo  = isset($defaultTipo) && $defaultTipo !== '' ? (string)$defaultTipo : 'Seguimiento';

include __DIR__ . '/../../partials/breadcrumbs.php';
?>

<section class="card ent-container">
  <h1 class="ent-title">Nuevo seguimiento</h1>
  <form class="seguimiento-form" method="post" action="<?= seguimiento_h($action) ?>">
    <div class="seguimiento-form__field">
      <label for="nuevo-fecha">Fecha de actividad</label>
      <input id="nuevo-fecha" type="date" name="fecha" value="<?= seguimiento_h($defaultFecha) ?>" required>
    </div>

    <div class="seguimiento-form__field">
      <label for="nuevo-coop">Cooperativa</label>
      <select id="nuevo-coop" name="id_cooperativa" required>
        <option value="">Seleccione</option>
        <?php foreach ($cooperativas as $coop): ?>
          <?php $value = isset($coop['id']) ? (string)$coop['id'] : ''; ?>
          <option value="<?= seguimiento_h($value) ?>"><?= seguimiento_h($coop['nombre'] ?? '') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="seguimiento-form__field">
      <label for="nuevo-tipo">Tipo de gestión</label>
      <select id="nuevo-tipo" name="tipo">
        <option value=""><?= seguimiento_h($defaultTipo) ?></option>
        <?php foreach ($tipos as $tipo): ?>
          <?php $tipoNombre = (string)$tipo; ?>
          <option value="<?= seguimiento_h($tipoNombre) ?>"><?= seguimiento_h($tipoNombre) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="seguimiento-form__field seguimiento-form__field--wide">
      <label for="nuevo-descripcion">Descripción</label>
      <textarea id="nuevo-descripcion" name="descripcion" rows="4" maxlength="600" required placeholder="Detalle del contacto o soporte realizado"></textarea>
    </div>

    <div class="seguimiento-form__field">
      <label for="nuevo-ticket">Ticket relacionado</label>
      <input id="nuevo-ticket" type="text" name="ticket" placeholder="Opcional">
    </div>

    <div class="seguimiento-form__actions">
      <button type="submit" class="btn btn-primary">
        <span class="material-symbols-outlined" aria-hidden="true">save</span>
        Guardar
      </button>
      <a class="btn btn-cancel" href="/comercial/eventos">Cancelar</a>
    </div>
  </form>
</section>
