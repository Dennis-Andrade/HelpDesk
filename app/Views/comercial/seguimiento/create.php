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

include __DIR__ . '/../../partials/breadcrumbs.php';
?>

<section class="card ent-container">
  <h1 class="ent-title">Nuevo seguimiento</h1>
  <form class="seguimiento-form" method="post" action="/comercial/eventos" data-seguimiento-create>
    <div class="seguimiento-form__row">
      <div class="seguimiento-form__field">
        <label for="nuevo-fecha-inicio">Fecha de inicio</label>
        <input id="nuevo-fecha-inicio" type="date" name="fecha_inicio" required>
      </div>
      <div class="seguimiento-form__field">
        <label for="nuevo-fecha-fin">Fecha de finalización</label>
        <input id="nuevo-fecha-fin" type="date" name="fecha_fin">
      </div>
    </div>

    <div class="seguimiento-form__field seguimiento-form__field--wide">
      <label for="nuevo-entidad">Entidad</label>
      <select id="nuevo-entidad" name="id_cooperativa" required>
        <option value="">Seleccione</option>
        <?php foreach ($cooperativas as $coop): ?>
          <?php $value = isset($coop['id']) ? (string)$coop['id'] : ''; ?>
          <option value="<?= seguimiento_h($value) ?>"><?= seguimiento_h($coop['nombre'] ?? '') ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="seguimiento-form__field">
      <label for="nuevo-tipo">Tipo de gestión</label>
      <select id="nuevo-tipo" name="tipo" required>
        <option value="">Seleccione</option>
        <?php foreach ($tipos as $tipo): ?>
          <?php $tipoNombre = (string)$tipo; ?>
          <option value="<?= seguimiento_h($tipoNombre) ?>"><?= seguimiento_h($tipoNombre) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="seguimiento-form__field seguimiento-form__field--wide">
      <label for="nuevo-descripcion">Descripción</label>
      <textarea id="nuevo-descripcion" name="descripcion" rows="4" maxlength="600" required placeholder="Detalle de la gestión realizada"></textarea>
    </div>

    <section class="seguimiento-form__section" data-seguimiento-section="contacto" hidden>
      <h2>Contacto relacionado</h2>
      <div class="seguimiento-form__field">
        <label for="nuevo-contacto">Seleccionar contacto</label>
        <select id="nuevo-contacto" name="id_contacto" data-section-required="true">
          <option value="">Seleccione</option>
        </select>
      </div>
      <div class="seguimiento-contacto-resumen" data-contacto-resumen>
        <div>
          <span>Nombre</span>
          <p data-contacto-dato="nombre"></p>
        </div>
        <div>
          <span>Celular</span>
          <p data-contacto-dato="telefono"></p>
        </div>
        <div>
          <span>Correo</span>
          <p data-contacto-dato="email"></p>
        </div>
      </div>
    </section>

    <section class="seguimiento-form__section" data-seguimiento-section="ticket" hidden>
      <h2>Ticket relacionado</h2>
      <div class="seguimiento-form__field">
        <label for="nuevo-ticket-buscar">Buscar ticket</label>
        <input id="nuevo-ticket-buscar" type="text" name="ticket_buscar" placeholder="Ej. INC-2025-00001" autocomplete="off" data-section-required="true">
        <datalist id="nuevo-ticket-opciones"></datalist>
        <input type="hidden" name="ticket_id" id="nuevo-ticket-id" value="">
        <input type="hidden" name="ticket_datos" id="nuevo-ticket-datos" value="">
      </div>
      <div class="seguimiento-ticket-resumen" data-ticket-resumen>
        <div>
          <span>Código</span>
          <p data-ticket-dato="codigo"></p>
        </div>
        <div>
          <span>Departamento</span>
          <p data-ticket-dato="departamento"></p>
        </div>
        <div>
          <span>Tipo incidencia</span>
          <p data-ticket-dato="tipo"></p>
        </div>
        <div>
          <span>Prioridad</span>
          <p data-ticket-dato="prioridad"></p>
        </div>
        <div>
          <span>Estado</span>
          <p data-ticket-dato="estado"></p>
        </div>
      </div>
    </section>

    <div class="seguimiento-form__actions">
      <button type="submit" class="btn btn-primary">
        <span class="material-symbols-outlined" aria-hidden="true">save</span>
        Guardar
      </button>
      <a class="btn btn-cancel" href="/comercial/eventos">Cancelar</a>
    </div>
  </form>
</section>
