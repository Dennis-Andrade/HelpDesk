(function(){
  const modal = document.getElementById('ent-card-modal');
  if (!modal) { return; }

  const dialog       = modal.querySelector('.ent-modal__box');
  const overlay      = modal.querySelector('.ent-modal__overlay');
  const closeButtons = modal.querySelectorAll('[data-close-modal]');
  const sentinelStart = modal.querySelector('[data-modal-sentinel="start"]');
  const sentinelEnd   = modal.querySelector('[data-modal-sentinel="end"]');
  const initialFocus  = modal.querySelector('[data-modal-initial-focus]');

  if (!dialog) { return; }

  const errorBox   = modal.querySelector('#ent-card-modal-error');
  const titleEl    = modal.querySelector('#ent-card-modal-title');
  const subtitleEl = modal.querySelector('#ent-card-modal-subtitle');
  const badgeEl    = modal.querySelector('#ent-card-modal-servicios');
  const fields = {
    ubicacion: modal.querySelector('#ent-md-ubicacion'),
    segmento:  modal.querySelector('#ent-md-segmento'),
    tipo:      modal.querySelector('#ent-md-tipo'),
    ruc:       modal.querySelector('#ent-md-ruc'),
    tfijo:     modal.querySelector('#ent-md-tfijo'),
    tmovil:    modal.querySelector('#ent-md-tmovil'),
    email:     modal.querySelector('#ent-md-email'),
    notas:     modal.querySelector('#ent-md-notas'),
    servicios: modal.querySelector('#ent-md-servicios')
  };

  const focusSelectors = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
  let focusableItems = [];
  let lastTrigger = null;

  function refreshFocusable() {
    focusableItems = Array.prototype.slice.call(dialog.querySelectorAll(focusSelectors))
      .filter(function(el){
        return !el.hasAttribute('disabled') && el.getAttribute('aria-hidden') !== 'true';
      });
    if (initialFocus && focusableItems.indexOf(initialFocus) === -1) {
      focusableItems.unshift(initialFocus);
    }
    if (!focusableItems.length) {
      focusableItems = [dialog];
    }
  }

  function setText(node, value) {
    if (!node) { return; }
    var text = value && String(value).trim() !== '' ? String(value).trim() : '—';
    node.textContent = text;
  }

  function clearServicios() {
    if (!fields.servicios) { return; }
    fields.servicios.textContent = '—';
  }

  function resetModal(){
    if (errorBox) {
      errorBox.textContent = '';
      errorBox.classList.remove('is-visible');
    }
    setText(titleEl, 'Entidad');
    setText(subtitleEl, '—');
    setText(badgeEl, '0 servicios');
    Object.keys(fields).forEach(function(key){ setText(fields[key], '—'); });
    clearServicios();
  }

  function showError(message) {
    if (!errorBox) { return; }
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
  }

  function focusFirstElement() {
    refreshFocusable();
    var target = focusableItems[0];
    if (target && typeof target.focus === 'function') {
      try {
        target.focus({ preventScroll: true });
      } catch (e) {
        target.focus();
      }
    } else {
      try {
        dialog.focus({ preventScroll: true });
      } catch (e) {
        dialog.focus();
      }
    }
  }

  function openModal(trigger){
    lastTrigger = trigger || document.activeElement || null;
    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-open');
    document.documentElement.style.overflow = 'hidden';
    focusFirstElement();
    dialog.addEventListener('keydown', handleDialogKeydown);
  }

  function closeModal(){
    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('is-open');
    document.documentElement.style.overflow = '';
    dialog.removeEventListener('keydown', handleDialogKeydown);
    if (lastTrigger && typeof lastTrigger.focus === 'function') {
      try {
        lastTrigger.focus({ preventScroll: true });
      } catch (e) {
        lastTrigger.focus();
      }
    }
    lastTrigger = null;
  }

  function handleDialogKeydown(ev) {
    if (ev.key === 'Escape') {
      ev.preventDefault();
      closeModal();
      return;
    }
    if (ev.key !== 'Tab') {
      return;
    }
    refreshFocusable();
    if (!focusableItems.length) {
      ev.preventDefault();
      dialog.focus();
      return;
    }
    var first = focusableItems[0];
    var last = focusableItems[focusableItems.length - 1];
    var active = document.activeElement;

    if (ev.shiftKey) {
      if (active === first || !dialog.contains(active)) {
        ev.preventDefault();
        last.focus();
      }
    } else if (active === last) {
      ev.preventDefault();
      first.focus();
    }
  }

  function handleSentinelFocus(which) {
    refreshFocusable();
    if (!focusableItems.length) {
      dialog.focus();
      return;
    }
    if (which === 'start') {
      focusableItems[focusableItems.length - 1].focus();
    } else {
      focusableItems[0].focus();
    }
  }

  if (sentinelStart) {
    sentinelStart.addEventListener('focus', function(){ handleSentinelFocus('start'); });
  }
  if (sentinelEnd) {
    sentinelEnd.addEventListener('focus', function(){ handleSentinelFocus('end'); });
  }

  closeButtons.forEach(function(btn){
    btn.addEventListener('click', function(ev){
      ev.preventDefault();
      closeModal();
    });
  });

  if (overlay) {
    overlay.addEventListener('click', function(){ closeModal(); });
  }

  function isModalOpen() {
    return modal.getAttribute('aria-hidden') === 'false';
  }

  document.addEventListener('keydown', function(ev){
    if (!isModalOpen()) { return; }
    if (ev.key === 'Escape') {
      ev.preventDefault();
      closeModal();
    }
  });

  async function fetchDetalle(id){
    var url = '/comercial/entidades/' + encodeURIComponent(id) + '/show';
    var response;
    try {
      response = await fetch(url, { headers: { 'Accept': 'application/json' } });
    } catch (networkError) {
      throw new Error('No se pudo conectar con el servidor.');
    }

    var payload = null;
    try {
      payload = await response.json();
    } catch (parseError) {
      if (!response.ok) {
        throw new Error('Error al obtener el detalle (HTTP ' + response.status + ')');
      }
      throw new Error('La respuesta del servidor no es válida.');
    }

    if (!response.ok) {
      var message = payload && payload.error ? String(payload.error) : 'Error al obtener el detalle (HTTP ' + response.status + ')';
      throw new Error(message);
    }

    return payload;
  }

  function renderServicios(data){
    if (!fields.servicios) { return; }
    var servicios = Array.isArray(data) ? data : [];
    if (!servicios.length) {
      clearServicios();
      return;
    }
    var list = document.createElement('ul');
    list.className = 'ent-card-phones';
    list.setAttribute('aria-label', 'Servicios activos');
    servicios.forEach(function(svc){
      var label = '';
      if (svc && typeof svc === 'object' && 'nombre_servicio' in svc) {
        label = String(svc.nombre_servicio || '');
      } else if (typeof svc === 'string') {
        label = svc;
      }
      label = label.trim();
      if (!label) { return; }
      var item = document.createElement('li');
      item.textContent = label;
      list.appendChild(item);
    });
    if (!list.childNodes.length) {
      clearServicios();
      return;
    }
    fields.servicios.innerHTML = '';
    fields.servicios.appendChild(list);
  }

  function formatServiciosLabel(count) {
    var total = typeof count === 'number' ? count : 0;
    if (total === 1) {
      return '1 servicio';
    }
    return total + ' servicios';
  }

  document.addEventListener('click', function(ev){
    var trigger = ev.target.closest('.js-entidad-view');
    if (!trigger) { return; }
    var id = trigger.getAttribute('data-entidad-id');
    if (!id) { return; }

    ev.preventDefault();
    resetModal();
    openModal(trigger);

    fetchDetalle(id).then(function(data){
      if (data && data.error) {
        throw new Error(String(data.error));
      }
      setText(titleEl, data && data.nombre ? data.nombre : 'Entidad');
      setText(subtitleEl, data && data.tipo ? data.tipo : '—');
      var servicios = Array.isArray(data && data.servicios) ? data.servicios : [];
      setText(badgeEl, formatServiciosLabel(servicios.length));
      setText(fields.ubicacion, data && data.ubicacion ? data.ubicacion : '—');
      setText(fields.segmento, data && data.segmento ? data.segmento : '—');
      setText(fields.tipo, data && data.tipo ? data.tipo : '—');
      setText(fields.ruc, data && data.ruc ? data.ruc : '—');
      setText(fields.tfijo, data && data.telefono_fijo ? data.telefono_fijo : '—');
      setText(fields.tmovil, data && data.telefono_movil ? data.telefono_movil : '—');
      setText(fields.email, data && data.email ? data.email : '—');
      setText(fields.notas, data && data.notas ? data.notas : '—');
      renderServicios(servicios);
    }).catch(function(err){
      showError(err && err.message ? err.message : 'No fue posible cargar el detalle.');
    });
  });
})();
