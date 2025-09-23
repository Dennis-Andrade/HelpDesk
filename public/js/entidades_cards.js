(function(){
  const modal = document.getElementById('ent-card-modal');
  if (!modal) return;

  const closeButtons = modal.querySelectorAll('.ent-card-modal__close');
  const focusableSelectors = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
  let lastFocus = null;

  const setText = (selector, value, fallback) => {
    const el = modal.querySelector(selector);
    if (!el) return;
    const text = value === null || value === undefined || value === '' ? (fallback ?? '—') : String(value);
    el.textContent = text;
  };

  const openModal = () => {
    lastFocus = document.activeElement;
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.style.overflow = 'hidden';
    const focusable = modal.querySelectorAll(focusableSelectors);
    if (focusable.length) {
      focusable[0].focus();
    }
  };

  const closeModal = () => {
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  };

  closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
  modal.addEventListener('click', event => {
    if (event.target === modal) {
      closeModal();
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
      closeModal();
    }
  });

  function formatServicios(servicios) {
    if (!Array.isArray(servicios)) {
      return [];
    }
    return servicios.reduce((acc, item) => {
      const label = item && typeof item === 'object'
        ? (item.nombre_servicio ?? item.nombre ?? item.label ?? '')
        : item;
      const text = String(label ?? '').trim();
      if (text !== '') {
        acc.push(text);
      }
      return acc;
    }, []);
  }

  async function loadEntity(id) {
    try {
      const response = await fetch(`/comercial/entidades/${encodeURIComponent(id)}/show`, {
        headers: { 'Accept': 'application/json' }
      });
      if (!response.ok) {
        throw new Error('Respuesta no válida');
      }
      const data = await response.json();
      if (data && data.error) {
        throw new Error(data.error);
      }

      setText('#ent-card-modal-title', data.nombre || 'Entidad');
      setText('#ent-card-modal-segmento', data.segmento || 'No especificado');
      const servicios = formatServicios(data.servicios || []);
      setText('#ent-card-modal-serv-count', `${servicios.length} servicios`);
      setText('#modal-ubicacion', data.ubicacion || 'No especificado');
      setText('#modal-tipo', data.tipo || 'No especificado');
      setText('#modal-ruc', data.ruc || '—');
      setText('#modal-telefono-fijo', data.telefono_fijo || '—');
      setText('#modal-telefono-movil', data.telefono_movil || '—');
      setText('#modal-email', data.email || '—');
      setText('#modal-notas', data.notas || '—');
      setText('#modal-servicios', servicios.length ? servicios.join('\n') : 'Sin servicios registrados');
      openModal();
    } catch (error) {
      console.error('No se pudo cargar la entidad', error);
      setText('#modal-servicios', 'No se pudo cargar la información');
      openModal();
    }
  }

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-entity-id]');
    if (!button) {
      return;
    }
    const id = button.getAttribute('data-entity-id');
    if (!id) {
      return;
    }
    loadEntity(id);
  });
})();
