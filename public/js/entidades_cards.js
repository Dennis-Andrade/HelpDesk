(function(){
  const modal = document.getElementById('ent-card-modal');
  if (!modal) { return; }

  const closeButtons = modal.querySelectorAll('[data-close-modal]');
  const errorBox     = modal.querySelector('#ent-card-modal-error');
  const titleEl      = modal.querySelector('#ent-card-modal-title');
  const subtitleEl   = modal.querySelector('#ent-card-modal-subtitle');
  const badgeEl      = modal.querySelector('#ent-card-modal-servicios');
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

  let lastTrigger = null;

  function setText(node, value) {
    if (!node) { return; }
    node.textContent = value && String(value).trim() !== '' ? String(value).trim() : '—';
  }

  function resetModal(){
    errorBox.textContent = '';
    setText(titleEl, 'Entidad');
    setText(subtitleEl, '—');
    setText(badgeEl, '0 servicios');
    Object.keys(fields).forEach(function(key){ setText(fields[key], '—'); });
    if (fields.servicios) {
      fields.servicios.innerHTML = '—';
    }
  }

  function openModal(trigger){
    lastTrigger = trigger || null;
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.style.overflow = 'hidden';
    const focusTarget = modal.querySelector('[data-close-modal]');
    if (focusTarget) { focusTarget.focus(); }
  }

  function closeModal(){
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
    if (lastTrigger && typeof lastTrigger.focus === 'function') {
      lastTrigger.focus();
    }
  }

  closeButtons.forEach(function(btn){
    btn.addEventListener('click', function(){ closeModal(); });
  });

  modal.addEventListener('click', function(ev){
    if (ev.target === modal) {
      closeModal();
    }
  });

  modal.addEventListener('keydown', function(ev){
    if (ev.key === 'Escape') {
      ev.preventDefault();
      closeModal();
    }
  });

  async function fetchDetalle(id){
    const url = '/comercial/entidades/' + encodeURIComponent(id) + '/show';
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) {
      throw new Error('Error al obtener el detalle (HTTP ' + res.status + ')');
    }
    return res.json();
  }

  function renderServicios(data){
    if (!fields.servicios) { return; }
    const servicios = Array.isArray(data) ? data : [];
    if (!servicios.length) {
      fields.servicios.textContent = '—';
      return;
    }
    const list = document.createElement('ul');
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
      const item = document.createElement('li');
      item.textContent = label;
      list.appendChild(item);
    });
    if (!list.childNodes.length) {
      fields.servicios.textContent = '—';
      return;
    }
    fields.servicios.innerHTML = '';
    fields.servicios.appendChild(list);
  }

  document.addEventListener('click', function(ev){
    const trigger = ev.target.closest('.js-entidad-view');
    if (!trigger) { return; }
    const id = trigger.getAttribute('data-entidad-id');
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
      const servicios = Array.isArray(data && data.servicios) ? data.servicios : [];
      var servicioCount = servicios.length;
      setText(badgeEl, servicioCount + ' servicios');
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
      errorBox.textContent = err && err.message ? err.message : 'No fue posible cargar el detalle.';
    });
  });
})();
