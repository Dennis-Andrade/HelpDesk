(function () {
  var contactCache = {};
  var ticketCache = {};
  var toastTimer = null;
  var CONTACT_TYPES = ['contacto', 'llamada', 'reunion', 'visita'];
  var TICKET_TYPES = ['ticket', 'soporte'];

  function normalizeType(value) {
    if (typeof value !== 'string') {
      return '';
    }
    var lower = value.trim().toLowerCase();
    if (typeof String.prototype.normalize === 'function') {
      lower = lower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return lower;
  }

  function getSectionVisibility(typeValue) {
    var normalized = normalizeType(typeValue);
    return {
      normalized: normalized,
      contacto: CONTACT_TYPES.indexOf(normalized) !== -1,
      ticket: TICKET_TYPES.indexOf(normalized) !== -1,
    };
  }

  function announce(message, variant) {
    if (!message) {
      return;
    }
    var region = document.querySelector('[data-seguimiento-toast]');
    if (!region) {
      region = document.createElement('div');
      region.className = 'seguimiento-toast';
      region.setAttribute('role', 'status');
      region.setAttribute('aria-live', 'polite');
      region.dataset.seguimientoToast = 'true';
      document.body.appendChild(region);
    }
    region.classList.remove('seguimiento-toast--success', 'seguimiento-toast--error', 'is-visible');
    region.textContent = message;
    if (variant === 'error') {
      region.classList.add('seguimiento-toast--error');
    } else {
      region.classList.add('seguimiento-toast--success');
    }
    region.classList.add('is-visible');
    if (toastTimer) {
      clearTimeout(toastTimer);
    }
    toastTimer = setTimeout(function () {
      region.classList.remove('is-visible');
    }, 3200);
  }

  function fetchJson(url, options) {
    return fetch(url, options || {}).then(function (response) {
      if (!response.ok) {
        throw new Error('Error al comunicarse con el servidor');
      }
      return response.json();
    });
  }

  function handleReset(button) {
    var form = button.closest('form');
    if (!form) {
      return;
    }
    var action = form.getAttribute('action') || window.location.pathname;
    window.location.href = action;
  }

  function setupTicketFilterSearch(form) {
    if (!form) {
      return;
    }
    var input = form.querySelector('[data-ticket-filter]');
    if (!input) {
      return;
    }
    var listId = input.getAttribute('list');
    var datalist = listId ? document.getElementById(listId) : null;
    var debounceTimer = null;

    function clearOptions() {
      if (!datalist) {
        return;
      }
      datalist.innerHTML = '';
    }

    function populate(options) {
      if (!datalist) {
        return;
      }
      datalist.innerHTML = '';
      options.forEach(function (item) {
        if (!item) {
          return;
        }
        var value = '';
        if (item.codigo && item.codigo !== '') {
          value = item.codigo;
        } else if (item.ticket_id) {
          value = 'Ticket #' + item.ticket_id;
        } else if (item.descripcion) {
          value = item.descripcion;
        }
        if (!value) {
          return;
        }
        var label = value;
        if (item.descripcion && item.descripcion !== value) {
          label = value + ' — ' + item.descripcion;
        }
        var option = document.createElement('option');
        option.value = value;
        option.label = label;
        option.textContent = label;
        datalist.appendChild(option);
      });
    }

    function performSearch(term) {
      var normalized = term.trim();
      if (normalized.length < 3) {
        clearOptions();
        return;
      }
      fetchJson('/comercial/eventos/sugerencias/tickets?q=' + encodeURIComponent(normalized))
        .then(function (response) {
          if (!response || !response.ok || !Array.isArray(response.items)) {
            return;
          }
          populate(response.items);
        })
        .catch(function () {
          clearOptions();
        });
    }

    if (input.value && input.value.trim().length >= 3) {
      performSearch(input.value);
    }

    input.addEventListener('input', function () {
      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }
      var term = input.value || '';
      debounceTimer = setTimeout(function () {
        performSearch(term);
      }, 220);
    });
  }

  function setupFilters(form) {
    if (!form) {
      return;
    }

    var toggle = form.querySelector('[data-action="seguimiento-toggle-filtros"]');
    var advanced = form.querySelector('[data-seguimiento-filters-advanced]');
    var initialAdvancedState = advanced && advanced.dataset && advanced.dataset.initiallyOpen === 'true';
    var openLabel = toggle ? toggle.querySelector('[data-label-open]') : null;
    var closeLabel = toggle ? toggle.querySelector('[data-label-close]') : null;

    function setState(expanded) {
      if (!toggle || !advanced) {
        return;
      }
      if (expanded) {
        advanced.hidden = false;
        advanced.removeAttribute('hidden');
        advanced.style.display = 'grid';
        advanced.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.dataset.expanded = 'true';
        if (openLabel) {
          openLabel.setAttribute('hidden', '');
        }
        if (closeLabel) {
          closeLabel.removeAttribute('hidden');
        }
      } else {
        advanced.hidden = true;
        advanced.setAttribute('hidden', '');
        advanced.style.display = 'none';
        advanced.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.dataset.expanded = 'false';
        if (openLabel) {
          openLabel.removeAttribute('hidden');
        }
        if (closeLabel) {
          closeLabel.setAttribute('hidden', '');
        }
      }
    }

    if (toggle && advanced) {
      var hasValue = false;
      advanced.querySelectorAll('input, select').forEach(function (field) {
        if (field.value && field.value !== '') {
          hasValue = true;
        }
      });

      setState(hasValue || initialAdvancedState);

      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        var expanded = toggle.dataset.expanded === 'true';
        setState(!expanded);
      });
    }

    setupTicketFilterSearch(form);
  }

  function setSelectOptions(select, items, selected) {
    if (!select) {
      return;
    }
    var current = select.value;
    while (select.options.length > 1) {
      select.remove(1);
    }
    items.forEach(function (item) {
      var option = document.createElement('option');
      option.value = String(item.id);
      option.textContent = item.nombre || ('Contacto #' + item.id);
      select.appendChild(option);
    });
    if (selected) {
      select.value = String(selected);
    } else {
      select.value = '';
    }
  }

  function renderContactInfo(container, contact) {
    if (!container) {
      return;
    }
    var fields = container.querySelectorAll('[data-contacto-dato]');
    fields.forEach(function (field) {
      var key = field.getAttribute('data-contacto-dato');
      var value = contact && key && contact[key] ? contact[key] : '—';
      field.textContent = value && value !== '' ? value : '—';
    });
  }

  function renderTicketInfo(container, data) {
    if (!container) {
      return;
    }
    var map = {
      codigo: data && data.codigo ? data.codigo : '—',
      departamento: data && data.departamento ? data.departamento : '—',
      tipo: data && data.tipo ? data.tipo : '—',
      prioridad: data && data.prioridad ? data.prioridad : '—',
      estado: data && data.estado ? data.estado : '—',
    };
    var fields = container.querySelectorAll('[data-ticket-dato]');
    fields.forEach(function (field) {
      var key = field.getAttribute('data-ticket-dato');
      field.textContent = key && Object.prototype.hasOwnProperty.call(map, key) ? map[key] : '—';
    });
  }

  function toggleSections(typeValue, sections) {
    var visibility = getSectionVisibility(typeValue);
    Object.keys(sections).forEach(function (key) {
      var section = sections[key];
      if (!section) {
        return;
      }
      var show = false;
      if (key === 'contacto') {
        show = visibility.contacto;
      } else if (key === 'ticket') {
        show = visibility.ticket;
      }
      if (show) {
        section.removeAttribute('hidden');
      } else {
        section.setAttribute('hidden', 'hidden');
      }
    });
    return visibility;
  }

  function disableFormFields(form, disabled) {
    if (!form) {
      return;
    }
    var fields = form.querySelectorAll('input[type="date"], input[type="text"], textarea, select');
    fields.forEach(function (field) {
      field.disabled = disabled;
    });
  }

  function loadContacts(entidadId) {
    if (!entidadId || entidadId <= 0) {
      return Promise.resolve([]);
    }
    if (contactCache[entidadId]) {
      return Promise.resolve(contactCache[entidadId]);
    }
    return fetchJson('/comercial/eventos/contactos?entidad=' + entidadId).then(function (response) {
      if (!response || !response.ok || !Array.isArray(response.items)) {
        return [];
      }
      contactCache[entidadId] = response.items;
      return response.items;
    }).catch(function () {
      return [];
    });
  }

  function setupTicketSearch(config) {
    var input = config.input;
    if (!input) {
      return;
    }
    var datalist = config.datalist;
    var hiddenId = config.hiddenId;
    var hiddenData = config.hiddenData;
    var summary = config.summary;
    var debounceTimer = null;
    var lookup = {};

    function writeHidden(ticket) {
      var ticketId = null;
      if (ticket) {
        if (ticket.id_ticket) {
          ticketId = ticket.id_ticket;
        } else if (ticket.id) {
          ticketId = ticket.id;
        }
      }
      if (hiddenId) {
        hiddenId.value = ticketId !== null && ticketId !== undefined && ticketId !== ''
          ? String(ticketId)
          : '';
      }
      if (hiddenData) {
        hiddenData.value = ticket ? JSON.stringify({
          id: ticketId,
          codigo: ticket.codigo || '',
          departamento: ticket.departamento || '',
          tipo: ticket.tipo || '',
          prioridad: ticket.prioridad || '',
          estado: ticket.estado || '',
        }) : '';
      }
      renderTicketInfo(summary, ticket);
    }

    function performSearch(term) {
      var normalized = term.trim();
      if (normalized.length < 3) {
        if (datalist) {
          datalist.innerHTML = '';
        }
        lookup = {};
        return;
      }
      fetchJson('/comercial/eventos/tickets/buscar?q=' + encodeURIComponent(normalized))
        .then(function (response) {
          if (!response || !response.ok || !Array.isArray(response.items)) {
            return;
          }
          lookup = {};
          if (datalist) {
            datalist.innerHTML = '';
          }
          response.items.forEach(function (item) {
            var key = item.codigo ? String(item.codigo).toUpperCase() : '';
            if (key) {
              lookup[key] = item;
            }
            if (datalist && item.codigo) {
              var option = document.createElement('option');
              option.value = item.codigo;
              option.label = item.codigo + ' — ' + (item.titulo || '');
              datalist.appendChild(option);
            }
          });
        })
        .catch(function () {
          /* noop */
        });
    }

    input.addEventListener('input', function () {
      if (debounceTimer) {
        clearTimeout(debounceTimer);
      }
      var term = input.value || '';
      debounceTimer = setTimeout(function () {
        performSearch(term);
      }, 250);
    });

    input.addEventListener('change', function () {
      var value = (input.value || '').trim();
      if (!value) {
        writeHidden(null);
        return;
      }
      var key = value.toUpperCase();
      var match = lookup[key];
      if (!match && ticketCache[key]) {
        match = ticketCache[key];
      }
      if (match) {
        ticketCache[key] = match;
        writeHidden(match);
      } else {
        // try fetching precise ticket by code
        fetchJson('/comercial/eventos/tickets/buscar?q=' + encodeURIComponent(value)).then(function (response) {
          if (response && Array.isArray(response.items) && response.items.length > 0) {
            var item = response.items[0];
            var newKey = item.codigo ? String(item.codigo).toUpperCase() : '';
            if (newKey) {
              lookup[newKey] = item;
              ticketCache[newKey] = item;
            }
            writeHidden(item);
          } else {
            writeHidden(null);
          }
        }).catch(function () {
          writeHidden(null);
        });
      }
    });

    if (hiddenId && hiddenId.value) {
      var existingId = parseInt(hiddenId.value, 10);
      if (!isNaN(existingId) && existingId > 0) {
        fetchJson('/comercial/eventos/tickets/' + existingId)
          .then(function (response) {
            if (response && response.ok && response.item) {
              var k = response.item.codigo ? String(response.item.codigo).toUpperCase() : '';
              if (k) {
                ticketCache[k] = response.item;
              }
              input.value = response.item.codigo || '';
              writeHidden(response.item);
            }
          })
          .catch(function () {
            /* noop */
          });
      }
    }
  }

  function setupCreateForm(form) {
    if (!form) {
      return;
    }
    var entidadSelect = form.querySelector('#nuevo-entidad');
    var tipoSelect = form.querySelector('#nuevo-tipo');
    var contactoSelect = form.querySelector('#nuevo-contacto');
    var contactoResumen = form.querySelector('[data-contacto-resumen]');
    var ticketInput = form.querySelector('#nuevo-ticket-buscar');
    var ticketDatalist = form.querySelector('#nuevo-ticket-opciones');
    var ticketIdField = form.querySelector('#nuevo-ticket-id');
    var ticketDatosField = form.querySelector('#nuevo-ticket-datos');
    var ticketResumen = form.querySelector('[data-ticket-resumen]');

    var sections = {
      contacto: form.querySelector('[data-seguimiento-section="contacto"]'),
      ticket: form.querySelector('[data-seguimiento-section="ticket"]'),
    };

    renderContactInfo(contactoResumen, null);
    renderTicketInfo(ticketResumen, null);

    function resetContactSection() {
      if (contactoSelect) {
        contactoSelect.value = '';
      }
      renderContactInfo(contactoResumen, null);
    }

    function resetTicketSection() {
      if (ticketInput) {
        ticketInput.value = '';
      }
      if (ticketIdField) {
        ticketIdField.value = '';
      }
      if (ticketDatosField) {
        ticketDatosField.value = '';
      }
      if (ticketDatalist) {
        ticketDatalist.innerHTML = '';
      }
      renderTicketInfo(ticketResumen, null);
    }

    if (entidadSelect) {
      entidadSelect.addEventListener('change', function () {
        var entidadId = parseInt(entidadSelect.value, 10);
        if (isNaN(entidadId) || entidadId <= 0) {
          setSelectOptions(contactoSelect, [], null);
          renderContactInfo(contactoResumen, null);
          return;
        }
        loadContacts(entidadId).then(function (items) {
          setSelectOptions(contactoSelect, items, null);
          renderContactInfo(contactoResumen, null);
        });
      });
    }

    if (contactoSelect) {
      contactoSelect.addEventListener('change', function () {
        var entidadId = entidadSelect ? parseInt(entidadSelect.value, 10) : 0;
        var contactId = parseInt(contactoSelect.value, 10);
        if (isNaN(entidadId) || entidadId <= 0 || isNaN(contactId) || contactId <= 0) {
          renderContactInfo(contactoResumen, null);
          return;
        }
        loadContacts(entidadId).then(function (items) {
          var found = items.find(function (item) {
            return item.id === contactId;
          });
          renderContactInfo(contactoResumen, found || null);
        });
      });
    }

    if (tipoSelect) {
      tipoSelect.addEventListener('change', function () {
        var visibility = toggleSections(tipoSelect.value, sections);
        if (!visibility.contacto) {
          resetContactSection();
        }
        if (!visibility.ticket) {
          resetTicketSection();
        }
      });
      var initialVisibility = toggleSections(tipoSelect.value, sections);
      if (!initialVisibility.contacto) {
        resetContactSection();
      }
      if (!initialVisibility.ticket) {
        resetTicketSection();
      }
    }

    setupTicketSearch({
      input: ticketInput,
      datalist: ticketDatalist,
      hiddenId: ticketIdField,
      hiddenData: ticketDatosField,
      summary: ticketResumen,
    });
  }

  function setupModal() {
    var modal = document.querySelector('[data-seguimiento-modal]');
    if (!modal) {
      return;
    }

    var overlay = modal.querySelector('[data-seguimiento-overlay]');
    var dialog = modal.querySelector('[data-seguimiento-dialog]');
    var closeBtn = modal.querySelector('[data-seguimiento-close]');
    var form = modal.querySelector('[data-seguimiento-form]');
    var editBtn = modal.querySelector('[data-seguimiento-edit]');
    var deleteBtn = modal.querySelector('[data-seguimiento-delete]');
    var titleEl = modal.querySelector('[data-seguimiento-modal-title]');
    var metaContainer = modal.querySelector('[data-seguimiento-modal-meta]');

    if (!form) {
      return;
    }

    var idField = form.querySelector('input[name="id"]');
    var fechaInicioField = form.querySelector('#modal-fecha-inicio');
    var fechaFinField = form.querySelector('#modal-fecha-fin');
    var entidadField = form.querySelector('#modal-entidad');
    var tipoField = form.querySelector('#modal-tipo');
    var descripcionField = form.querySelector('#modal-descripcion');
    var contactoSelect = form.querySelector('#modal-contacto');
    var contactoResumen = form.querySelector('[data-contacto-resumen]');
    var ticketInput = form.querySelector('#modal-ticket-buscar');
    var ticketDatalist = form.querySelector('#modal-ticket-opciones');
    var ticketIdField = form.querySelector('#modal-ticket-id');
    var ticketDatosField = form.querySelector('#modal-ticket-datos');
    var ticketResumen = form.querySelector('[data-ticket-resumen]');

    var sections = {
      contacto: form.querySelector('[data-seguimiento-section="contacto"]'),
      ticket: form.querySelector('[data-seguimiento-section="ticket"]'),
    };

    var currentData = null;
    var currentCard = null;
    var editing = false;
    var lastFocused = null;

    setupTicketSearch({
      input: ticketInput,
      datalist: ticketDatalist,
      hiddenId: ticketIdField,
      hiddenData: ticketDatosField,
      summary: ticketResumen,
    });

    function resetModalContactSection() {
      if (contactoSelect) {
        contactoSelect.value = '';
      }
      renderContactInfo(contactoResumen, null);
    }

    function resetModalTicketSection() {
      if (ticketInput) {
        ticketInput.value = '';
      }
      if (ticketIdField) {
        ticketIdField.value = '';
      }
      if (ticketDatosField) {
        ticketDatosField.value = '';
      }
      if (ticketDatalist) {
        ticketDatalist.innerHTML = '';
      }
      renderTicketInfo(ticketResumen, null);
    }

    if (entidadField) {
      entidadField.addEventListener('change', function () {
        if (!editing) {
          return;
        }
        var entidadId = parseInt(entidadField.value, 10);
        if (isNaN(entidadId) || entidadId <= 0) {
          setSelectOptions(contactoSelect, [], null);
          renderContactInfo(contactoResumen, null);
          return;
        }
        loadContacts(entidadId).then(function (items) {
          setSelectOptions(contactoSelect, items, null);
          renderContactInfo(contactoResumen, null);
        });
      });
    }

    if (tipoField) {
      tipoField.addEventListener('change', function () {
        var visibility = toggleSections(tipoField.value, sections);
        if (!visibility.contacto) {
          resetModalContactSection();
        }
        if (!visibility.ticket) {
          resetModalTicketSection();
        }
      });
    }

    if (contactoSelect) {
      contactoSelect.addEventListener('change', function () {
        if (!currentData) {
          return;
        }
        var entidadId = parseInt(entidadField ? entidadField.value : '0', 10);
        var contactId = parseInt(contactoSelect.value, 10);
        if (isNaN(entidadId) || entidadId <= 0 || isNaN(contactId) || contactId <= 0) {
          renderContactInfo(contactoResumen, null);
          return;
        }
        loadContacts(entidadId).then(function (items) {
          var found = items.find(function (item) { return item.id === contactId; });
          renderContactInfo(contactoResumen, found || null);
        });
      });
    }

    function renderMeta(data) {
      if (!metaContainer) {
        return;
      }
      metaContainer.innerHTML = '';
      var chips = [];
      if (data.usuario) {
        chips.push({ icon: 'person', text: 'Registrado por ' + data.usuario });
      }
      if (data.creado_en) {
        chips.push({ icon: 'schedule', text: data.creado_en });
      }
      if (data.editado_en) {
        chips.push({ icon: 'update', text: 'Actualizado ' + data.editado_en });
      }
      if (data.id) {
        chips.push({ icon: 'tag', text: 'ID #' + data.id });
      }
      if (chips.length === 0) {
        metaContainer.setAttribute('hidden', 'hidden');
        return;
      }
      metaContainer.removeAttribute('hidden');
      chips.forEach(function (chip) {
        var span = document.createElement('span');
        span.className = 'seguimiento-modal__meta-item';
        var icon = document.createElement('span');
        icon.className = 'material-symbols-outlined';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = chip.icon;
        var text = document.createElement('span');
        text.textContent = chip.text;
        span.appendChild(icon);
        span.appendChild(text);
        metaContainer.appendChild(span);
      });
    }

    function applyData(data) {
      if (!data) {
        return;
      }
      var entityName = data.entidad || data.cooperativa || '';
      if (!data.entidad && entityName) {
        data.entidad = entityName;
      }
      if (!data.cooperativa && entityName) {
        data.cooperativa = entityName;
      }
      disableFormFields(form, true);
      editing = false;
      if (editBtn) {
        editBtn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">edit</span>Editar';
      }
      if (idField) {
        idField.value = data.id ? String(data.id) : '';
      }
      if (fechaInicioField) {
        fechaInicioField.value = data.fecha_inicio || '';
      }
      if (fechaFinField) {
        fechaFinField.value = data.fecha_fin || '';
      }
      if (entidadField) {
        entidadField.value = data.id_cooperativa ? String(data.id_cooperativa) : '';
      }
      if (tipoField) {
        setSelectValue(tipoField, data.tipo || '');
      }
      if (descripcionField) {
        descripcionField.value = data.descripcion || '';
      }
      if (titleEl) {
        titleEl.textContent = entityName || 'Detalle de seguimiento';
      }
      var visibility = toggleSections(data.tipo || '', sections);
      if (!visibility.contacto) {
        resetModalContactSection();
      }
      if (!visibility.ticket) {
        resetModalTicketSection();
      }

      if (entidadField && contactoSelect) {
        var entidadId = parseInt(entidadField.value, 10);
        if (!isNaN(entidadId) && entidadId > 0) {
          loadContacts(entidadId).then(function (items) {
            setSelectOptions(contactoSelect, items, data.id_contacto || null);
            var found = items.find(function (item) { return data.id_contacto && item.id === data.id_contacto; });
            renderContactInfo(contactoResumen, found || null);
          });
        } else {
          setSelectOptions(contactoSelect, [], null);
          renderContactInfo(contactoResumen, null);
        }
      }

      if (ticketInput) {
        ticketInput.value = data.ticket_codigo || '';
      }
      if (ticketIdField) {
        ticketIdField.value = data.ticket_id ? String(data.ticket_id) : '';
      }
      if (ticketDatosField) {
        ticketDatosField.value = data.datos_ticket ? JSON.stringify(data.datos_ticket) : '';
      }
      renderTicketInfo(ticketResumen, data.datos_ticket || {
        codigo: data.ticket_codigo || '',
        departamento: data.ticket_departamento || '',
        tipo: data.ticket_tipo || '',
        prioridad: data.ticket_prioridad || '',
        estado: data.ticket_estado || '',
      });

      renderMeta(data);
    }

    function setSelectValue(select, value) {
      if (!select) {
        return;
      }
      var normalized = value ? String(value) : '';
      var exists = false;
      for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === normalized) {
          exists = true;
          break;
        }
      }
      if (!exists && normalized !== '') {
        var option = document.createElement('option');
        option.value = normalized;
        option.textContent = normalized;
        option.setAttribute('data-generated', 'true');
        select.appendChild(option);
      }
      select.value = normalized;
    }

    function toggleEdit() {
      if (!currentData) {
        return;
      }
      editing = !editing;
      if (editing) {
        disableFormFields(form, false);
        if (editBtn) {
          editBtn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">save</span>Guardar';
        }
        if (fechaInicioField && typeof fechaInicioField.focus === 'function') {
          fechaInicioField.focus();
        }
      } else {
        disableFormFields(form, true);
        if (editBtn) {
          editBtn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">edit</span>Editar';
        }
      }
      toggleSections(tipoField ? tipoField.value : '', sections);
    }

    function closeModal() {
      if (!modal.classList.contains('is-open')) {
        return;
      }
      modal.classList.remove('is-open');
      modal.setAttribute('hidden', 'hidden');
      document.body.classList.remove('seguimiento-modal-open');
      disableFormFields(form, true);
      editing = false;
      if (editBtn) {
        editBtn.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">edit</span>Editar';
      }
      if (lastFocused && typeof lastFocused.focus === 'function') {
        lastFocused.focus();
      }
      currentCard = null;
      currentData = null;
    }

    function openModal(card, data) {
      currentCard = card;
      currentData = data;
      if (currentData && !currentData.entidad && currentData.cooperativa) {
        currentData.entidad = currentData.cooperativa;
      }
      lastFocused = document.activeElement;
      modal.removeAttribute('hidden');
      modal.classList.add('is-open');
      document.body.classList.add('seguimiento-modal-open');
      applyData(data);
      var focusTarget = closeBtn || dialog;
      if (focusTarget && typeof focusTarget.focus === 'function') {
        setTimeout(function () {
          focusTarget.focus();
        }, 80);
      }
    }

    function formatDate(value) {
      if (!value) {
        return '';
      }
      var parts = String(value).split('-');
      if (parts.length === 3) {
        return parts[2] + '/' + parts[1] + '/' + parts[0];
      }
      return value;
    }

    function refreshCard(data) {
      if (!currentCard) {
        return;
      }
      var payload = {
        id: data.id,
        id_cooperativa: data.id_cooperativa,
        entidad: data.entidad || data.cooperativa || '',
        cooperativa: data.cooperativa || data.entidad || '',
        fecha_inicio: data.fecha_inicio,
        fecha_inicio_texto: data.fecha_inicio ? formatDate(data.fecha_inicio) : '',
        fecha_fin: data.fecha_fin,
        fecha_fin_texto: data.fecha_fin ? formatDate(data.fecha_fin) : '',
        tipo: data.tipo,
        descripcion: data.descripcion,
        contacto_id: data.id_contacto,
        contacto_nombre: data.contacto_nombre,
        contacto_telefono: data.contacto_telefono,
        contacto_email: data.contacto_email,
        ticket_id: data.ticket_id,
        ticket_codigo: data.ticket_codigo,
        ticket_departamento: data.ticket_departamento,
        ticket_tipo: data.ticket_tipo,
        ticket_prioridad: data.ticket_prioridad,
        ticket_estado: data.ticket_estado,
        datos_reunion: data.datos_reunion,
        datos_ticket: data.datos_ticket,
        usuario: data.usuario,
        creado_en: data.creado_en,
        editado_en: data.editado_en,
      };
      try {
        currentCard.setAttribute('data-item', JSON.stringify(payload));
      } catch (error) {
        currentCard.setAttribute('data-item', '{}');
      }
      var title = currentCard.querySelector('.seguimiento-card__title');
      if (title) {
        title.textContent = payload.cooperativa || '';
      }
      var desc = currentCard.querySelector('.seguimiento-card__desc');
      if (desc) {
        desc.textContent = data.descripcion || '';
      }
      var badge = currentCard.querySelector('.seguimiento-card__badge');
      if (badge) {
        badge.textContent = data.tipo || '';
      }
      var inicioEl = currentCard.querySelector('[data-field="inicio"]');
      if (inicioEl) {
        var inicioTexto = formatDate(data.fecha_inicio);
        inicioEl.textContent = inicioTexto;
        inicioEl.classList.toggle('seguimiento-card__value--empty', !inicioTexto);
      }
      var finEl = currentCard.querySelector('[data-field="fin"]');
      if (finEl) {
        var finTexto = formatDate(data.fecha_fin);
        finEl.textContent = finTexto;
        finEl.classList.toggle('seguimiento-card__value--empty', !finTexto);
      }
      var usuarioEl = currentCard.querySelector('[data-field="usuario"]');
      if (usuarioEl) {
        var usuarioTexto = data.usuario || '';
        usuarioEl.textContent = usuarioTexto;
        usuarioEl.classList.toggle('seguimiento-card__value--empty', !usuarioTexto);
      }
    }

    function submitUpdate() {
      if (!currentData || !idField || !idField.value) {
        return;
      }
      var formData = new FormData(form);
      fetch('/comercial/eventos/' + encodeURIComponent(idField.value), {
        method: 'POST',
        body: formData,
      })
        .then(function (response) {
          return response.json().catch(function () { return {}; });
        })
        .then(function (payload) {
          if (!payload || !payload.ok || !payload.item) {
            var message = 'No se pudo actualizar el seguimiento.';
            if (payload && Array.isArray(payload.errors) && payload.errors.length) {
              message = payload.errors.join(' ');
            }
            announce(message, 'error');
            return;
          }
          currentData = payload.item;
          if (currentData && !currentData.entidad && currentData.cooperativa) {
            currentData.entidad = currentData.cooperativa;
          }
          applyData(currentData);
          refreshCard(currentData);
          announce('Información actualizada correctamente', 'success');
        })
        .catch(function () {
          announce('Ocurrió un error al actualizar el seguimiento.', 'error');
        });
    }

    if (editBtn) {
      editBtn.addEventListener('click', function () {
        if (!currentData) {
          return;
        }
        if (!editing) {
          toggleEdit();
        } else {
          submitUpdate();
        }
      });
    }

    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        if (!currentData || !currentData.id) {
          console.error('No hay id para eliminar:', currentData);
          return;
        }

        if (!window.confirm('¿Estás seguro de eliminar el seguimiento?')) {
          return;
        }

        var cardToRemove = currentCard;
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrf = csrfMeta && typeof csrfMeta.getAttribute === 'function' ? csrfMeta.getAttribute('content') : '';
        var url = '/comercial/eventos/' + encodeURIComponent(currentData.id) + '/eliminar';
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrf) {
          headers['X-CSRF-TOKEN'] = csrf;
        }

        fetch(url, {
          method: 'POST',
          headers: Object.assign({}, headers, { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }),
          body: new URLSearchParams({ _method: 'DELETE' }).toString(),
        })
          .then(function (res) {
            return res.json().catch(function () {
              return {};
            });
          })
          .then(function (payload) {
            if (!payload || !payload.ok) {
              var message = 'No se pudo eliminar el seguimiento.';
              if (payload && Array.isArray(payload.errors) && payload.errors.length) {
                message = payload.errors.join(' ');
              }
              announce(message, 'error');
              return;
            }
            closeModal();
            if (cardToRemove && cardToRemove.parentElement) {
              cardToRemove.parentElement.removeChild(cardToRemove);
            }
            announce('Seguimiento eliminado correctamente.', 'success');
          })
          .catch(function (err) {
            console.error('Error delete:', err);
            announce('Ocurrió un error al eliminar el seguimiento.', 'error');
          });
      });
    }

    if (overlay) {
      overlay.addEventListener('click', closeModal);
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
      });
    }

    modal.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal();
      }
    });

    if (dialog) {
      dialog.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    disableFormFields(form, true);

    if (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
      });
    }

    document.querySelectorAll('[data-seguimiento-card]').forEach(function (card) {
      function parseData() {
        var raw = card.getAttribute('data-item');
        if (!raw) {
          return null;
        }
        try {
          var parsed = JSON.parse(raw);
          if (parsed && !parsed.cooperativa && parsed.entidad) {
            parsed.cooperativa = parsed.entidad;
          }
          if (parsed && !parsed.entidad && parsed.cooperativa) {
            parsed.entidad = parsed.cooperativa;
          }
          return parsed;
        } catch (error) {
          return null;
        }
      }
      card.addEventListener('click', function () {
        var data = parseData();
        if (data) {
          openModal(card, data);
        }
      });
      card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          var data = parseData();
          if (data) {
            openModal(card, data);
          }
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var resetBtn = document.querySelector('[data-action="seguimiento-reset"]');
    if (resetBtn) {
      resetBtn.addEventListener('click', function (event) {
        event.preventDefault();
        handleReset(resetBtn);
      });
    }

    var filtersForm = document.querySelector('.seguimiento-filters');
    setupFilters(filtersForm);
    setupCreateForm(document.querySelector('[data-seguimiento-create]'));
    setupModal();
  });
})();
