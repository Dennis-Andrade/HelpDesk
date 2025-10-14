
(function () {
  function handleReset(button) {
    var form = button.closest('form');
    if (!form) {
      return;
    }
    var fechaField = form.querySelector('#seguimiento-fecha');
    var defaultValue = fechaField ? fechaField.getAttribute('data-default') : '';
    form.reset();
    if (fechaField && defaultValue) {
      fechaField.value = defaultValue;
    }
    form.submit();
  }

  function parseCardData(card) {
    if (!card) {
      return null;
    }
    var raw = card.getAttribute('data-item');
    if (!raw) {
      return null;
    }
    try {
      return JSON.parse(raw);
    } catch (error) {
      return null;
    }
  }

  function removeGeneratedOptions(select) {
    if (!select) {
      return;
    }
    var generated = select.querySelectorAll('option[data-generated="true"]');
    generated.forEach(function (option) {
      if (option.parentNode) {
        option.parentNode.removeChild(option);
      }
    });
  }

  function setSelectValue(select, value, label) {
    if (!select) {
      return;
    }
    var normalized = value === null || value === undefined ? '' : String(value);
    removeGeneratedOptions(select);

    var found = false;
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].value === normalized) {
        found = true;
        break;
      }
    }

    if (!found && normalized !== '') {
      var option = document.createElement('option');
      option.value = normalized;
      option.textContent = label && label !== '' ? label : normalized;
      option.setAttribute('data-generated', 'true');
      select.appendChild(option);
      found = true;
    }

    if (found) {
      select.value = normalized;
    } else {
      select.value = '';
    }
  }

  function setFieldValue(field, value) {
    if (!field) {
      return;
    }
    field.value = value === null || value === undefined ? '' : String(value);
  }

  function stringFromContact(data, fallbackText) {
    if (typeof fallbackText === 'string' && fallbackText !== '') {
      return fallbackText;
    }
    if (!data) {
      return '';
    }
    if (typeof data === 'string') {
      return data;
    }
    if (Array.isArray(data)) {
      return data.join(', ');
    }
    if (typeof data === 'object') {
      var pieces = [];
      for (var key in data) {
        if (!Object.prototype.hasOwnProperty.call(data, key)) {
          continue;
        }
        var value = data[key];
        if (value === null || value === '') {
          continue;
        }
        var normalizedValue = value;
        if (typeof value === 'object') {
          try {
            normalizedValue = JSON.stringify(value);
          } catch (error) {
            normalizedValue = '';
          }
        }
        if (normalizedValue === '') {
          continue;
        }
        if (key && key !== '') {
          pieces.push(key + ': ' + normalizedValue);
        } else {
          pieces.push(String(normalizedValue));
        }
      }
      return pieces.join('; ');
    }
    return '';
  }

  function createMetaChip(icon, text) {
    var chip = document.createElement('span');
    chip.className = 'seguimiento-modal__meta-item';
    var iconEl = document.createElement('span');
    iconEl.className = 'material-symbols-outlined';
    iconEl.setAttribute('aria-hidden', 'true');
    iconEl.textContent = icon;
    var textEl = document.createElement('span');
    textEl.textContent = text;
    chip.appendChild(iconEl);
    chip.appendChild(textEl);
    return chip;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var resetBtn = document.querySelector('[data-action="seguimiento-reset"]');
    if (resetBtn) {
      resetBtn.addEventListener('click', function (event) {
        event.preventDefault();
        handleReset(resetBtn);
      });
    }

    var modal = document.querySelector('[data-seguimiento-modal]');
    if (!modal) {
      return;
    }

    var overlay = modal.querySelector('[data-seguimiento-overlay]');
    var dialog = modal.querySelector('[data-seguimiento-dialog]');
    var closeBtn = modal.querySelector('[data-seguimiento-close]');
    var cancelBtn = modal.querySelector('[data-seguimiento-cancel]');
    var form = modal.querySelector('[data-seguimiento-form]');
    var idField = form ? form.querySelector('input[name="id"]') : null;
    var fechaField = form ? form.querySelector('#modal-fecha') : null;
    var coopField = form ? form.querySelector('#modal-coop') : null;
    var tipoField = form ? form.querySelector('#modal-tipo') : null;
    var descripcionField = form ? form.querySelector('#modal-descripcion') : null;
    var ticketField = form ? form.querySelector('#modal-ticket') : null;
    var contactoField = form ? form.querySelector('#modal-contacto') : null;
    var contactoDetalleField = form ? form.querySelector('#modal-contacto-detalle') : null;
    var metaContainer = modal.querySelector('[data-seguimiento-modal-meta]');
    var titleEl = modal.querySelector('[data-seguimiento-modal-title]');
    var subtitleEl = modal.querySelector('[data-seguimiento-modal-subtitle]');
    var editBtn = modal.querySelector('[data-seguimiento-edit]');
    var deleteBtn = modal.querySelector('[data-seguimiento-delete]');

    var currentData = null;
    var lastFocusedElement = null;

    function closeModal() {
      if (!modal.classList.contains('is-open')) {
        return;
      }
      modal.classList.remove('is-open');
      modal.setAttribute('hidden', 'hidden');
      document.body.classList.remove('seguimiento-modal-open');
      currentData = null;
      if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
        lastFocusedElement.focus();
      }
    }

    function renderMeta(data) {
      if (!metaContainer) {
        return;
      }
      metaContainer.innerHTML = '';
      var chips = [];
      if (data.usuario) {
        chips.push(createMetaChip('person', 'Registrado por ' + data.usuario));
      }
      if (data.creado_en) {
        chips.push(createMetaChip('schedule', data.creado_en));
      }
      if (data.contact_number !== null && data.contact_number !== undefined && data.contact_number !== '' && data.contact_number !== 0) {
        chips.push(createMetaChip('call', 'Contacto ' + data.contact_number));
      }
      if (data.id) {
        chips.push(createMetaChip('tag', 'ID #' + data.id));
      }

      if (chips.length === 0) {
        metaContainer.setAttribute('hidden', 'hidden');
        return;
      }

      metaContainer.removeAttribute('hidden');
      chips.forEach(function (chip) {
        metaContainer.appendChild(chip);
      });
    }

    function openModal(data) {
      if (!data) {
        return;
      }
      currentData = data;
      lastFocusedElement = document.activeElement;
      modal.removeAttribute('hidden');
      modal.classList.add('is-open');
      document.body.classList.add('seguimiento-modal-open');

      if (idField) {
        idField.value = data.id ? String(data.id) : '';
      }
      var fechaValor = data.fecha !== undefined && data.fecha !== null ? data.fecha : '';
      var coopValor = data.cooperativa_id !== undefined && data.cooperativa_id !== null ? data.cooperativa_id : '';
      var tipoValor = data.tipo !== undefined && data.tipo !== null ? data.tipo : '';
      var descripcionValor = data.descripcion !== undefined && data.descripcion !== null ? data.descripcion : '';
      var ticketValor = data.ticket !== undefined && data.ticket !== null ? data.ticket : '';
      var contactoValor = data.contact_number !== undefined && data.contact_number !== null && data.contact_number !== 0
        ? data.contact_number
        : '';

      setFieldValue(fechaField, fechaValor);
      setSelectValue(coopField, coopValor, data.cooperativa || '');
      setSelectValue(tipoField, tipoValor, data.tipo || '');
      setFieldValue(descripcionField, descripcionValor);
      setFieldValue(ticketField, ticketValor);
      setFieldValue(contactoField, contactoValor);
      var contactoTexto = stringFromContact(data.contact_data, data.contact_data_text || '');
      setFieldValue(contactoDetalleField, contactoTexto);

      if (titleEl) {
        titleEl.textContent = data.cooperativa && data.cooperativa !== ''
          ? data.cooperativa
          : 'Detalle de seguimiento';
      }

      if (subtitleEl) {
        var subtitleParts = [];
        if (data.fecha_texto) {
          subtitleParts.push(data.fecha_texto);
        }
        if (data.tipo) {
          subtitleParts.push(data.tipo);
        }
        if (data.ticket) {
          subtitleParts.push('Ticket ' + data.ticket);
        }
        subtitleEl.textContent = subtitleParts.length > 0 ? subtitleParts.join(' · ') : '';
      }

      if (editBtn) {
        editBtn.disabled = !data.id;
      }
      if (deleteBtn) {
        deleteBtn.disabled = !data.id;
      }

      renderMeta(data);

      if (fechaField && typeof fechaField.focus === 'function') {
        setTimeout(function () {
          fechaField.focus();
        }, 60);
      }
    }

    var cards = document.querySelectorAll('[data-seguimiento-card]');
    cards.forEach(function (card) {
      card.addEventListener('click', function () {
        var data = parseCardData(card);
        if (data) {
          openModal(data);
        }
      });

      card.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          var data = parseCardData(card);
          if (data) {
            openModal(data);
          }
        }
      });
    });

    if (overlay) {
      overlay.addEventListener('click', closeModal);
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
      });
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
      });
    }

    if (modal) {
      modal.addEventListener('click', function (event) {
        if (event.target === modal) {
          closeModal();
        }
      });
      modal.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          event.preventDefault();
          closeModal();
        }
      });
    }

    if (dialog) {
      dialog.addEventListener('click', function (event) {
        event.stopPropagation();
      });
    }

    if (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
      });
    }

    if (editBtn) {
      editBtn.addEventListener('click', function () {
        if (!currentData || !currentData.id) {
          return;
        }
        var editEvent = new CustomEvent('seguimiento:edit', {
          detail: currentData,
        });
        document.dispatchEvent(editEvent);
      });
    }

    if (deleteBtn) {
      deleteBtn.addEventListener('click', function () {
        if (!currentData || !currentData.id) {
          return;
        }
        var deleteEvent = new CustomEvent('seguimiento:delete', {
          detail: currentData,
        });
        document.dispatchEvent(deleteEvent);
      });
    }
  });
})();
