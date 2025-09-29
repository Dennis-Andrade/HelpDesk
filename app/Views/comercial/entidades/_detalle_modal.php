<?php
/**
 * Modal de detalle dinámico de entidades.
 */
?>
<div id="ent-card-modal" class="ent-modal" data-modal aria-hidden="true">
  <div class="ent-modal__overlay" data-close-modal tabindex="-1" aria-hidden="true"></div>
  <div class="ent-modal__box"
       role="dialog"
       aria-modal="true"
       aria-labelledby="ent-card-modal-title"
       aria-describedby="ent-card-modal-subtitle ent-card-modal-error"
       tabindex="-1">
    <div tabindex="0" data-modal-sentinel="start"></div>
    <button type="button" class="ent-modal__close" aria-label="Cerrar" data-close-modal>
      <span class="material-symbols-outlined" aria-hidden="true">close</span>
    </button>
    <div class="ent-modal__header ent-card-head">
      <div class="ent-card-icon" aria-hidden="true">
        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
      </div>
      <div class="ent-modal__titles">
        <h2 id="ent-card-modal-title" class="ent-card-title">Entidad</h2>
        <p id="ent-card-modal-subtitle" class="ent-card-subtitle">—</p>
      </div>
      <span id="ent-card-modal-servicios" class="ent-badge" aria-live="polite">0 servicios</span>
    </div>
    <div class="ent-modal__body">
      <div id="ent-card-modal-error" class="ent-modal__error" role="alert" aria-live="assertive"></div>
      <dl class="ent-details">
        <div><dt>Ubicación</dt><dd id="ent-md-ubicacion">—</dd></div>
        <div><dt>Segmento</dt><dd id="ent-md-segmento">—</dd></div>
        <div><dt>Tipo</dt><dd id="ent-md-tipo">—</dd></div>
        <div><dt>RUC</dt><dd id="ent-md-ruc">—</dd></div>
        <div><dt>Teléfono fijo</dt><dd id="ent-md-tfijo">—</dd></div>
        <div><dt>Teléfono móvil</dt><dd id="ent-md-tmovil">—</dd></div>
        <div><dt>Correo</dt><dd id="ent-md-email">—</dd></div>
        <div><dt>Notas</dt><dd id="ent-md-notas">—</dd></div>
        <div><dt>Servicios activos</dt><dd id="ent-md-servicios">—</dd></div>
      </dl>
    </div>
    <div class="ent-modal__footer">
      <button type="button" class="btn btn-outline" data-close-modal data-modal-initial-focus>Cerrar</button>
    </div>
    <div tabindex="0" data-modal-sentinel="end"></div>
  </div>
</div>
