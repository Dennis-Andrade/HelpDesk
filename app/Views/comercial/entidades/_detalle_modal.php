<?php
/**
 * Modal estático para mostrar el detalle de una entidad.
 */
?>
<div id="ent-detalle-modal" class="ent-modal" data-modal aria-hidden="true">
  <div class="ent-modal__overlay" data-modal-close></div>
  <div class="ent-modal__box" role="dialog" aria-modal="true" aria-labelledby="ent-modal-title" tabindex="-1">
    <header class="ent-card-head ent-modal__header">
      <div class="ent-card-icon" aria-hidden="true">
        <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
      </div>
      <div class="ent-modal__titles">
        <h2 id="ent-modal-title" class="ent-card-title" data-field="nombre">Entidad seleccionada</h2>
        <p class="ent-card-subtitle" data-field="segmento">Segmento</p>
      </div>
      <button type="button" class="ent-modal__close" data-modal-close aria-label="Cerrar detalle">
        <span class="material-symbols-outlined" aria-hidden="true">close</span>
      </button>
    </header>
    <section class="ent-modal__body">
      <dl class="ent-modal__list">
        <div class="ent-modal__row">
          <dt>Nombre comercial</dt>
          <dd data-field="nombre_completo">No especificado</dd>
        </div>
        <div class="ent-modal__row">
          <dt>RUC</dt>
          <dd data-field="ruc">No especificado</dd>
        </div>
        <div class="ent-modal__row">
          <dt>Ubicación</dt>
          <dd data-field="ubicacion">No especificado</dd>
        </div>
        <div class="ent-modal__row">
          <dt>Teléfonos</dt>
          <dd>
            <ul class="ent-modal__list-inline" data-field="telefonos">
              <li>No especificado</li>
            </ul>
          </dd>
        </div>
        <div class="ent-modal__row">
          <dt>Correo electrónico</dt>
          <dd data-field="email">No especificado</dd>
        </div>
        <div class="ent-modal__row">
          <dt>Servicios</dt>
          <dd>
            <ul class="ent-modal__list-inline" data-field="servicios">
              <li>Sin registros</li>
            </ul>
          </dd>
        </div>
        <div class="ent-modal__row">
          <dt>Notas</dt>
          <dd data-field="notas">No especificado</dd>
        </div>
      </dl>
      <div class="ent-modal__error" role="alert" aria-live="assertive"></div>
    </section>
  </div>
</div>
