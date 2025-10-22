/* global fetch */
(function () {
  'use strict';

  var REQUIRED_CONTACT_TYPES = {
    conciliacion: true,
    cobranza: true,
    reunion: true,
    visita: true
  };
  var SHOW_TICKET_TYPES = {
    soporte: true,
    ticket: true
  };

  function normalize(value) {
    if (typeof value !== 'string') {
      return '';
    }
    var lower = value.trim().toLowerCase();
    if (typeof lower.normalize === 'function') {
      lower = lower.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return lower;
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Error de red');
        }
        return response.json();
      });
  }

  function renderOptions(select, items, selectedValue, labelKey) {
    if (!select) {
      return;
    }
    var doc = select.ownerDocument;
    select.innerHTML = '';
    var placeholder = doc.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Seleccione';
    select.appendChild(placeholder);

    (items || []).forEach(function (item) {
      if (!item) { return; }
      var option = doc.createElement('option');
      var value = typeof item.id !== 'undefined' ? String(item.id) : '';
      option.value = value;
      var label = item[labelKey] || item.nombre || item.codigo || value;
      option.textContent = label;
      if (String(selectedValue) === value) {
        option.selected = true;
      }
      select.appendChild(option);
    });
  }

  function updateContactSection(form) {
    if (!form) { return; }
    var typeSelect = form.querySelector('[data-seguimiento-tipo]');
    var wrapper = form.querySelector('[data-contacto-wrapper]');
    if (!typeSelect || !wrapper) { return; }
    var normalized = normalize(typeSelect.value);
    var visible = !!REQUIRED_CONTACT_TYPES[normalized];
    wrapper.hidden = !visible;
    var select = wrapper.querySelector('select');
    if (select) {
      select.required = visible;
      if (!visible) {
        select.value = '';
      }
    }
  }

  function updateTicketSection(form) {
    if (!form) { return; }
    var typeSelect = form.querySelector('[data-seguimiento-tipo]');
    var wrapper = form.querySelector('[data-ticket-wrapper]');
    if (!typeSelect || !wrapper) { return; }
    var normalized = normalize(typeSelect.value);
    var visible = !!SHOW_TICKET_TYPES[normalized];
    wrapper.hidden = !visible;
    var hiddenInput = form.querySelector('[data-ticket-id]');
    var preview = form.querySelector('[data-ticket-preview]');
    if (!visible && hiddenInput) {
      hiddenInput.value = '';
      if (preview) {
        preview.hidden = true;
        preview.innerHTML = '';
      }
    }
  }

  function setupTypeHandlers(form) {
    var typeSelect = form.querySelector('[data-seguimiento-tipo]');
    if (!typeSelect) { return; }
    typeSelect.addEventListener('change', function () {
      updateContactSection(form);
      updateTicketSection(form);
    });
    updateContactSection(form);
    updateTicketSection(form);
  }

  function loadContacts(form, entidadId, selectedId) {
    var select = form.querySelector('[data-contacto-select]');
    var wrapper = form.querySelector('[data-contacto-wrapper]');
    if (!select || !wrapper) {
      return;
    }

    if (!entidadId) {
      renderOptions(select, [], null, 'nombre');
      return;
    }

    var endpoint = select.getAttribute('data-contactos-url') || '/contabilidad/seguimiento/contactos';
    endpoint += (endpoint.indexOf('?') === -1 ? '?' : '&') + 'entidad=' + encodeURIComponent(entidadId);

    fetchJson(endpoint)
      .then(function (response) {
        if (!response || !response.ok) {
          throw new Error();
        }
        renderOptions(select, response.items || [], selectedId, 'nombre');
        updateContactSection(form);
      })
      .catch(function () {
        renderOptions(select, [], null, 'nombre');
      });
  }

  function loadContratos(form, entidadId, selectedId) {
    var select = form.querySelector('[data-contrato-select]');
    if (!select) { return; }

    if (!entidadId) {
      renderOptions(select, [], null, 'codigo');
      var placeholder = select.querySelector('option');
      if (placeholder) {
        placeholder.textContent = 'Sin contrato';
      }
      return;
    }

    var base = select.getAttribute('data-contratos-url') || '/contabilidad/seguimiento/contratos';
    var url = base + (base.indexOf('?') === -1 ? '?' : '&') + 'entidad=' + encodeURIComponent(entidadId);
    fetchJson(url)
      .then(function (response) {
        if (!response || !response.ok) {
          throw new Error();
        }
        var items = response.items || [];
        renderOptions(select, items, selectedId, 'codigo');
        var placeholder = select.querySelector('option');
        if (placeholder) {
          placeholder.textContent = 'Sin contrato';
        }
      })
      .catch(function () {
        renderOptions(select, [], null, 'codigo');
        var placeholder = select.querySelector('option');
        if (placeholder) {
          placeholder.textContent = 'Sin contrato';
        }
      });
  }

  function setupEntidadHandler(form) {
    var select = form.querySelector('#seguimiento-entidad');
    if (!select) { return; }

    select.addEventListener('change', function () {
      var entidadId = select.value;
      loadContacts(form, entidadId, null);
      loadContratos(form, entidadId, null);
    });

    if (select.value) {
      var selectedContact = form.querySelector('[data-contacto-select] option[selected]');
      var selectedContactId = selectedContact ? selectedContact.value : null;
      loadContacts(form, select.value, selectedContactId);
      var selectedContrato = form.querySelector('[data-contrato-select] option[selected]');
      var selectedContratoId = selectedContrato ? selectedContrato.value : null;
      loadContratos(form, select.value, selectedContratoId);
    }
  }

  function renderTicketPreview(container, ticket) {
    if (!container) { return; }
    if (!ticket) {
      container.hidden = true;
      container.innerHTML = '';
      return;
    }
    container.hidden = false;
    container.innerHTML = ''
      + '<strong>' + csegEscape(ticket.codigo || '') + '</strong>'
      + '<p>' + csegEscape(ticket.titulo || ticket.asunto || '') + '</p>'
      + '<p class="seguimiento-meta">Prioridad: ' + csegEscape(ticket.prioridad || '') + ' · Estado: ' + csegEscape(ticket.estado || '') + '</p>';
  }

  function csegEscape(value) {
    var div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
  }

  function setupTicketSearch(form) {
    var input = form.querySelector('[data-ticket-input]');
    var hidden = form.querySelector('[data-ticket-id]');
    var button = form.querySelector('[data-ticket-buscar]');
    var preview = form.querySelector('[data-ticket-preview]');
    if (!input || !hidden) { return; }

    function search(term) {
      var normalized = term.trim();
      if (normalized.length < 3) {
        renderTicketPreview(preview, null);
        hidden.value = '';
        return;
      }
      fetchJson('/contabilidad/seguimiento/sugerencias/tickets?q=' + encodeURIComponent(normalized))
        .then(function (response) {
          if (!response || !response.ok || !Array.isArray(response.items) || !response.items.length) {
            renderTicketPreview(preview, null);
            hidden.value = '';
            return;
          }
          var ticket = response.items[0];
          hidden.value = ticket.id;
          input.value = ticket.codigo || ticket.titulo || '';
          renderTicketPreview(preview, ticket);
        })
        .catch(function () {
          renderTicketPreview(preview, null);
          hidden.value = '';
        });
    }

    if (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        search(input.value || '');
      });
    }

    var debounce = null;
    input.addEventListener('input', function () {
      if (debounce) {
        clearTimeout(debounce);
      }
      debounce = setTimeout(function () {
        search(input.value || '');
      }, 320);
    });

    if (hidden.value) {
      fetchJson('/contabilidad/seguimiento/tickets/' + encodeURIComponent(hidden.value))
        .then(function (response) {
          if (response && response.ok) {
            renderTicketPreview(preview, response.item || null);
          }
        })
        .catch(function () {});
    }
  }

  function setupResetButtons() {
    document.querySelectorAll('[data-action="seguimiento-reset"]').forEach(function (button) {
      button.addEventListener('click', function () {
        var form = button.closest('form');
        if (!form) { return; }
        var action = form.getAttribute('action') || window.location.pathname;
        window.location.href = action;
      });
    });
  }

  function initForms() {
    document.querySelectorAll('[data-contab-seguimiento-form]').forEach(function (form) {
      setupTypeHandlers(form);
      setupEntidadHandler(form);
      setupTicketSearch(form);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initForms();
    setupResetButtons();
  });
})();
