(function () {
  var modal = document.getElementById('ent-modal');
  if (!modal) {
    return;
  }

  var dialog = modal.querySelector('.ent-modal__dialog');
  var backdrop = modal.querySelector('[data-dismiss="modal"]');
  var content = document.getElementById('ent-modal-content');
  var closeButtons = modal.querySelectorAll('[data-dismiss="modal"]');
  var title = document.getElementById('ent-modal-title');
  var lastTrigger = null;

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function cleanArray(value) {
    if (Array.isArray(value)) {
      return value
        .map(function (item) { return typeof item === 'string' ? item.trim() : String(item || '').trim(); })
        .filter(function (item) { return item !== ''; });
    }
    if (typeof value === 'string') {
      return value.split(',').map(function (item) { return item.trim(); }).filter(function (item) { return item !== ''; });
    }
    return [];
  }

  function renderRow(label, body) {
    return '<div><dt>' + escapeHtml(label) + '</dt><dd>' + body + '</dd></div>';
  }

  function renderList(values) {
    if (!values.length) {
      return '<span>No especificado</span>';
    }
    var items = values.map(function (value) {
      return '<li>' + escapeHtml(value) + '</li>';
    }).join('');
    return '<ul class="ent-modal__list">' + items + '</ul>';
  }

  function renderData(data) {
    if (!content) {
      return;
    }
    var telefono = cleanArray(data.telefono);
    var correo = cleanArray(data.email);
    var servicios = cleanArray(data.servicios);
    var ubicacion = '';
    if (data.provincia && data.canton) {
      ubicacion = data.provincia + ' – ' + data.canton;
    } else if (data.provincia) {
      ubicacion = data.provincia;
    } else if (data.canton) {
      ubicacion = data.canton;
    }

    var rows = '';
    rows += renderRow('Ubicación', ubicacion ? escapeHtml(ubicacion) : 'No especificado');
    rows += renderRow('Segmento', data.segmento ? escapeHtml(data.segmento) : 'No especificado');
    rows += renderRow('RUC / Cédula', data.ruc ? escapeHtml(data.ruc) : 'No especificado');
    rows += renderRow('Teléfonos', renderList(telefono));
    rows += renderRow('Email', renderList(correo));
    rows += renderRow('Servicios', renderList(servicios));

    content.innerHTML = '<dl class="ent-modal__details">' + rows + '</dl>';
  }

  function showError(message) {
    if (!content) {
      return;
    }
    var text = message || 'No se pudo obtener la entidad.';
    content.innerHTML = '<p class="ent-modal__error">' + escapeHtml(text) + '</p>';
  }

  function openModal(trigger) {
    lastTrigger = trigger || null;
    modal.hidden = false;
    modal.classList.add('is-open');
    document.body.classList.add('has-modal-open');
    trapFocus();
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.hidden = true;
    document.body.classList.remove('has-modal-open');
    if (lastTrigger && typeof lastTrigger.focus === 'function') {
      try {
        lastTrigger.focus();
      } catch (err) {
        lastTrigger.focus();
      }
    }
    lastTrigger = null;
  }

  function getFocusable() {
    if (!dialog) {
      return [];
    }
    var selectors = 'a[href], button:not([disabled]), textarea:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
    return Array.prototype.slice.call(dialog.querySelectorAll(selectors))
      .filter(function (el) { return el.offsetParent !== null; });
  }

  function trapFocus() {
    var focusable = getFocusable();
    if (!focusable.length) {
      dialog.setAttribute('tabindex', '-1');
      dialog.focus();
    } else {
      focusable[0].focus();
    }
  }

  function handleKeydown(event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeModal();
      return;
    }
    if (event.key !== 'Tab') {
      return;
    }
    var focusable = getFocusable();
    if (!focusable.length) {
      event.preventDefault();
      dialog.focus();
      return;
    }
    var first = focusable[0];
    var last = focusable[focusable.length - 1];
    var active = document.activeElement;
    if (event.shiftKey) {
      if (active === first || !dialog.contains(active)) {
        event.preventDefault();
        last.focus();
      }
    } else if (active === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function loadDetalle(id, trigger) {
    if (!content) {
      return;
    }
    content.innerHTML = '<p class="ent-modal__loading">Cargando...</p>';
    if (title) {
      title.textContent = 'Detalle de entidad';
    }

    fetch('/comercial/entidades/' + encodeURIComponent(id) + '/show', {
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('No se pudo obtener la entidad.');
      }
      return response.json();
    }).then(function (payload) {
      if (payload && payload.error) {
        throw new Error(payload.error);
      }
      if (!payload || !payload.data) {
        throw new Error('Respuesta inválida del servidor.');
      }
      var data = payload.data;
      if (title) {
        title.textContent = data.nombre ? String(data.nombre) : 'Detalle de entidad';
      }
      renderData(data);
      openModal(trigger);
    }).catch(function (error) {
      showError(error && error.message ? error.message : 'No se pudo obtener la entidad.');
      openModal(trigger);
    });
  }

  modal.addEventListener('keydown', handleKeydown);

  Array.prototype.slice.call(closeButtons).forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      closeModal();
    });
  });

  if (backdrop) {
    backdrop.addEventListener('click', function () {
      closeModal();
    });
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target;
    if (!trigger) {
      return;
    }
    if (trigger.classList && trigger.classList.contains('ent-card-view')) {
      event.preventDefault();
      var entityId = trigger.getAttribute('data-id');
      if (!entityId) {
        return;
      }
      loadDetalle(entityId, trigger);
    }
  });
})();
