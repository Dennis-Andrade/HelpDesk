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
        <div class="ent-details__item">
          <dt class="ent-details__term">Ubicación</dt>
          <dd class="ent-details__value" id="ent-md-ubicacion">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Dirección específica</dt>
          <dd class="ent-details__value" id="ent-md-direccion">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Segmento</dt>
          <dd class="ent-details__value ent-details__value--badge" id="ent-md-segmento">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Tipo</dt>
          <dd class="ent-details__value ent-details__value--accent" id="ent-md-tipo">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">RUC</dt>
          <dd class="ent-details__value" id="ent-md-ruc">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Teléfono fijo</dt>
          <dd class="ent-details__value" id="ent-md-tfijo">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Teléfono móvil</dt>
          <dd class="ent-details__value" id="ent-md-tmovil">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Correo</dt>
          <dd class="ent-details__value ent-details__value--link" id="ent-md-email">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Notas</dt>
          <dd class="ent-details__value ent-details__value--notes" id="ent-md-notas">—</dd>
        </div>
        <div class="ent-details__item ent-details__item--services">
          <dt class="ent-details__term">Servicios activos</dt>
          <dd class="ent-details__value ent-details__value--services" id="ent-md-servicios">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Facturación total</dt>
          <dd class="ent-details__value" id="ent-md-facturacion">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">SIC · Licencias</dt>
          <dd class="ent-details__value" id="ent-md-sic">—</dd>
        </div>
        <div class="ent-details__item">
          <dt class="ent-details__term">Registrada el</dt>
          <dd class="ent-details__value" id="ent-md-fecha">—</dd>
        </div>
      </dl>
    </div>
    <div class="ent-modal__footer">
      <button type="button" class="btn btn-outline" data-close-modal data-modal-initial-focus>Cerrar</button>
    </div>
    <div tabindex="0" data-modal-sentinel="end"></div>
  </div>
</div>
