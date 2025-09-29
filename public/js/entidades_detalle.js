(function(){
  'use strict';

  var modal = document.getElementById('ent-detalle-modal');
  if (!modal) {
    return;
  }

  var MODAL_OPEN_CLASS = 'is-open';
  var dataCache = null;
  var lastTrigger = null;
  var modalBox = modal.querySelector('.ent-modal__box');

  var fieldRefs = {
    nombre: modal.querySelector('[data-field="nombre"]'),
    segmento: modal.querySelector('[data-field="segmento"]'),
    nombre_completo: modal.querySelector('[data-field="nombre_completo"]'),
    ruc: modal.querySelector('[data-field="ruc"]'),
    ubicacion: modal.querySelector('[data-field="ubicacion"]'),
    telefonos: modal.querySelector('[data-field="telefonos"]'),
    email: modal.querySelector('[data-field="email"]'),
    servicios: modal.querySelector('[data-field="servicios"]'),
    notas: modal.querySelector('[data-field="notas"]')
  };
  var errorBox = modal.querySelector('.ent-modal__error');

  function fetchCatalog(){
    if (dataCache) {
      return Promise.resolve(dataCache);
    }
    return fetch('/data/entidades_detalle.json', { cache: 'no-cache' })
      .then(function(response){
        if (!response.ok) {
          throw new Error('No se pudo cargar el catálogo de entidades');
        }
        return response.json();
      })
      .then(function(payload){
        var records = Array.isArray(payload) ? payload : payload && Array.isArray(payload.entidades) ? payload.entidades : [];
        var map = {};
        for (var i = 0; i < records.length; i += 1) {
          var item = records[i];
          if (!item || typeof item !== 'object') {
            continue;
          }
          var key = String(item.id != null ? item.id : item.codigo || '');
          if (key === '') {
            continue;
          }
          map[key] = item;
        }
        dataCache = map;
        return dataCache;
      });
  }

  function formatLocation(detail){
    var provincia = (detail && detail.provincia ? String(detail.provincia) : '').trim();
    var canton = (detail && detail.canton ? String(detail.canton) : '').trim();
    if (provincia && canton) {
      return provincia + ' – ' + canton;
    }
    if (provincia) {
      return provincia;
    }
    if (canton) {
      return canton;
    }
    return 'No especificado';
  }

  function fillList(listEl, values, emptyText){
    if (!listEl) {
      return;
    }
    while (listEl.firstChild) {
      listEl.removeChild(listEl.firstChild);
    }
    var items = [];
    if (Array.isArray(values)) {
      for (var i = 0; i < values.length; i += 1) {
        var val = values[i];
        if (val == null) {
          continue;
        }
        var trimmed = String(val).trim();
        if (trimmed !== '') {
          items.push(trimmed);
        }
      }
    } else if (values != null && String(values).trim() !== '') {
      items.push(String(values).trim());
    }
    if (items.length === 0) {
      var emptyLi = document.createElement('li');
      emptyLi.textContent = emptyText;
      listEl.appendChild(emptyLi);
      return;
    }
    items.forEach(function(value){
      var li = document.createElement('li');
      li.textContent = value;
      listEl.appendChild(li);
    });
  }

  function resetError(){
    if (!errorBox) {
      return;
    }
    errorBox.textContent = '';
    errorBox.classList.remove('is-visible');
  }

  function showError(message){
    if (!errorBox) {
      return;
    }
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
  }

  function renderDetail(detail){
    var nombre = detail && detail.nombre ? String(detail.nombre) : 'Entidad sin nombre';
    if (fieldRefs.nombre) {
      fieldRefs.nombre.textContent = nombre;
    }
    if (fieldRefs.segmento) {
      fieldRefs.segmento.textContent = detail && detail.segmento ? String(detail.segmento) : 'Segmento no especificado';
    }
    if (fieldRefs.nombre_completo) {
      var nombreComercial = detail && detail.nombre_comercial ? String(detail.nombre_comercial) : nombre;
      fieldRefs.nombre_completo.textContent = nombreComercial || 'No especificado';
    }
    if (fieldRefs.ruc) {
      fieldRefs.ruc.textContent = detail && detail.ruc ? String(detail.ruc) : 'No especificado';
    }
    if (fieldRefs.ubicacion) {
      fieldRefs.ubicacion.textContent = formatLocation(detail);
    }
    fillList(fieldRefs.telefonos, detail && detail.telefonos, 'No especificado');
    if (fieldRefs.email) {
      var emailValue = detail && detail.email ? String(detail.email).trim() : '';
      fieldRefs.email.textContent = emailValue !== '' ? emailValue : 'No especificado';
    }
    fillList(fieldRefs.servicios, detail && detail.servicios, 'Sin registros');
    if (fieldRefs.notas) {
      var notas = detail && detail.notas ? String(detail.notas).trim() : '';
      fieldRefs.notas.textContent = notas !== '' ? notas : 'No especificado';
    }
  }

  function openModal(){
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add(MODAL_OPEN_CLASS);
    if (modalBox) {
      modalBox.focus({ preventScroll: true });
    }
  }

  function closeModal(){
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove(MODAL_OPEN_CLASS);
    resetError();
    if (lastTrigger && typeof lastTrigger.focus === 'function') {
      lastTrigger.focus();
    }
  }

  modal.addEventListener('click', function(evt){
    if (evt.target && evt.target.hasAttribute('data-modal-close')) {
      closeModal();
    }
  });

  modal.addEventListener('keydown', function(evt){
    if (evt.key === 'Escape') {
      evt.preventDefault();
      closeModal();
    }
  });

  function handleTriggerClick(evt){
    evt.preventDefault();
    var trigger = evt.currentTarget;
    var id = trigger.getAttribute('data-entidad-view');
    if (!id) {
      return;
    }
    lastTrigger = trigger;
    fetchCatalog()
      .then(function(map){
        resetError();
        var detail = map[String(id)];
        if (!detail) {
          renderDetail({ nombre: 'Entidad no encontrada' });
          showError('No se encontraron datos para la entidad seleccionada.');
        } else {
          renderDetail(detail);
        }
        openModal();
      })
      .catch(function(error){
        renderDetail({ nombre: 'Error al cargar' });
        showError(error && error.message ? error.message : 'Ocurrió un error inesperado.');
        openModal();
      });
  }

  var triggers = document.querySelectorAll('.ent-card-view[data-entidad-view]');
  for (var i = 0; i < triggers.length; i += 1) {
    triggers[i].addEventListener('click', handleTriggerClick);
  }
})();
