<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$estados = is_array($estados ?? null) ? $estados : ['Nuevo', 'Reparado', 'Dañado'];
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="card ent-container">
  <h1 class="ent-title">Registrar equipo</h1>
  <form method="post" action="/contabilidad/inventario" class="form ent-form" enctype="multipart/form-data">
    <div class="form-grid">
      <div class="form-field">
        <label for="equipo-nombre">Nombre *</label>
        <input id="equipo-nombre" type="text" name="nombre" required maxlength="150" placeholder="Ej.: Laptop Lenovo ThinkPad">
      </div>
      <div class="form-field">
        <label for="equipo-codigo">Código patrimonial *</label>
        <input id="equipo-codigo" type="text" name="codigo" required maxlength="80" placeholder="Ej.: EQ-2025-001">
      </div>
      <div class="form-field">
        <label for="equipo-estado">Estado *</label>
        <select id="equipo-estado" name="estado" required class="select">
          <?php foreach ($estados as $estado): ?>
            <option value="<?= htmlspecialchars((string)$estado, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$estado, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label for="equipo-fecha">Fecha de entrega *</label>
        <input id="equipo-fecha" type="date" name="fecha_entrega" required value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="form-field">
        <label for="equipo-responsable">Responsable *</label>
        <input id="equipo-responsable" type="text" name="responsable" required maxlength="120" placeholder="Nombre del colaborador">
      </div>
      <div class="form-field">
        <label for="equipo-contacto">Contacto del responsable</label>
        <input id="equipo-contacto" type="text" name="responsable_contacto" maxlength="80" placeholder="Teléfono o correo">
      </div>
      <div class="form-field">
        <label for="equipo-serie">Serie</label>
        <input id="equipo-serie" type="text" name="serie" maxlength="120" placeholder="Número de serie del fabricante">
      </div>
      <div class="form-field">
        <label for="equipo-marca">Marca</label>
        <input id="equipo-marca" type="text" name="marca" maxlength="60" placeholder="Marca comercial">
      </div>
      <div class="form-field">
        <label for="equipo-modelo">Modelo</label>
        <input id="equipo-modelo" type="text" name="modelo" maxlength="60" placeholder="Modelo del equipo">
      </div>
      <div class="form-field form-field--full">
        <label for="equipo-descripcion">Descripción</label>
        <textarea id="equipo-descripcion" name="descripcion" rows="3" placeholder="Detalle del equipo, accesorios entregados, condiciones."></textarea>
      </div>
      <div class="form-field form-field--full">
        <label for="equipo-comentarios">Comentarios</label>
        <textarea id="equipo-comentarios" name="comentarios" rows="3" placeholder="Observaciones internas, pendientes o recordatorios."></textarea>
      </div>
      <div class="form-field form-field--full">
        <label for="equipo-documento">Archivo adjunto (PDF o imagen)</label>
        <input id="equipo-documento" type="file" name="documento" accept="application/pdf,image/*">
        <span class="form-hint">Adjunta el acta de entrega, reparación o evidencia del estado del equipo.</span>
      </div>
    </div>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-outlined" aria-hidden="true">save</span>
        Guardar
      </button>
      <a class="btn btn-cancel" href="/contabilidad/inventario">Cancelar</a>
    </div>
  </form>
</section>
