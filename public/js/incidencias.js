(function() {
  const body = document.body;
  const config = window.__INCIDENCIAS_CONFIG__ || {};
  const tiposPorDepartamento = config.tipos || {};
  let activeController = null;

  function obtenerTipos(departamentoId) {
    if (!departamentoId) {
      return [];
    }
    const key = String(departamentoId);
    const lista = tiposPorDepartamento[key];
    return Array.isArray(lista) ? lista : [];
  }

  function renderTipoOptions(select, departamentoId, selectedId, selectedNombre, selectedGlobalId) {
    if (!select) {
      return { hasOptions: false, selectedGlobalId: '' };
    }

    const tipos = obtenerTipos(departamentoId);
    const doc = select.ownerDocument;
    const normalizedId = selectedId !== undefined && selectedId !== null && selectedId !== ''
      ? String(selectedId)
      : '';
    const normalizedNombre = selectedNombre ? String(selectedNombre).toLowerCase() : '';
    const normalizedGlobal = selectedGlobalId !== undefined && selectedGlobalId !== null && selectedGlobalId !== ''
      ? String(selectedGlobalId)
      : '';

    select.innerHTML = '';

    const placeholder = doc.createElement('option');
    placeholder.value = '';
    placeholder.textContent = departamentoId
      ? (tipos.length ? 'Seleccione' : 'Sin tipos disponibles')
      : 'Seleccione un departamento';
    placeholder.dataset.globalId = '';
    select.appendChild(placeholder);

    let matched = false;
    tipos.forEach(function(tipo) {
      if (!tipo) {
        return;
      }
      const option = doc.createElement('option');
      const value = typeof tipo.id !== 'undefined' ? String(tipo.id) : '';
      const optionNombre = typeof tipo.nombre === 'string' ? tipo.nombre : '';
      const optionGlobal = typeof tipo.globalId !== 'undefined' && tipo.globalId !== null
        ? String(tipo.globalId)
        : '';

      option.value = value;
      option.textContent = optionNombre;
      option.dataset.globalId = optionGlobal;

      if (!matched && normalizedGlobal && optionGlobal && optionGlobal === normalizedGlobal) {
        option.selected = true;
        matched = true;
      } else if (!matched && normalizedId && value === normalizedId) {
        option.selected = true;
        matched = true;
      } else if (!matched && normalizedNombre && optionNombre.toLowerCase() === normalizedNombre) {
        option.selected = true;
        matched = true;
      }

      select.appendChild(option);
    });

    if (!matched) {
      placeholder.selected = true;
    }

    const hasOptions = !!departamentoId && tipos.length > 0;
    select.disabled = !hasOptions;
    if (!hasOptions) {
      select.setAttribute('aria-disabled', 'true');
    } else {
      select.removeAttribute('aria-disabled');
    }

    let finalGlobal = '';
    if (select.selectedIndex >= 0) {
      const selectedOption = select.options[select.selectedIndex];
      if (selectedOption && selectedOption.dataset) {
        finalGlobal = selectedOption.dataset.globalId || '';
      }
    }

    return { hasOptions: hasOptions, selectedGlobalId: finalGlobal };
  }

  function updateHiddenFromSelect(select, hiddenInput, fallbackValue) {
    if (!hiddenInput) {
      return;
    }

    let value = '';
    if (select && select.selectedIndex >= 0) {
      const option = select.options[select.selectedIndex];
      if (option && option.dataset && typeof option.dataset.globalId !== 'undefined') {
        value = option.dataset.globalId || '';
      }
    }

    if (!value && (fallbackValue || fallbackValue === 0)) {
      value = String(fallbackValue || '');
    }

    hiddenInput.value = value;
  }

  function syncDepartamentoSelect(select, departamentoId, nombre) {
    if (!select) {
      return;
    }

    if (departamentoId && select.querySelector('option[value="' + String(departamentoId) + '"]')) {
      select.value = String(departamentoId);
      return;
    }

    if (!nombre) {
      select.value = '';
      return;
    }

    const lowerName = String(nombre).toLowerCase();
    Array.from(select.options).forEach(function(option) {
      if (option.textContent && option.textContent.toLowerCase() === lowerName) {
        option.selected = true;
      }
    });
  }

  function attachModal(modal, options) {
    if (!modal) {
      return null;
    }

    const overlay = modal.querySelector('.incidencias-modal__overlay');
    const closeButtons = modal.querySelectorAll('[data-incidencia-close]');
    const opts = options || {};

    if (!modal.hasAttribute('hidden')) {
      modal.setAttribute('hidden', 'hidden');
    }

    const controller = {
      modal: modal,
      open: function() {
        modal.removeAttribute('hidden');
        if (typeof opts.onBeforeOpen === 'function') {
          opts.onBeforeOpen(controller);
        }

        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-active');
        activeController = controller;
        body.classList.add('modal-open');

        window.requestAnimationFrame(function() {
          if (typeof opts.onAfterOpen === 'function') {
            opts.onAfterOpen(controller);
          }

          if (!modal.contains(document.activeElement)) {
            modal.focus();
          }
        });
      },
      close: function() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-active');

        if (typeof opts.onAfterClose === 'function') {
          opts.onAfterClose(controller);
        }

        if (activeController === controller) {
          activeController = null;
        }

        if (!document.querySelector('.incidencias-modal.is-active')) {
          body.classList.remove('modal-open');
        }

        window.setTimeout(function() {
          if (!modal.classList.contains('is-active')) {
            modal.setAttribute('hidden', 'hidden');
          }
        }, 210);
      }
    };

    if (overlay) {
      overlay.addEventListener('click', controller.close);
    }

    closeButtons.forEach(function(btn) {
      btn.addEventListener('click', controller.close);
    });

    modal.addEventListener('click', function(evt) {
      if (evt.target === modal) {
        controller.close();
      }
    });

    return controller;
  }

  const createForm = document.getElementById('incidencias-create-form');
  const createDeptSelect = document.getElementById('create-departamento');
  const createTipoSelect = document.getElementById('create-tipo');
  const createTipoGlobalInput = document.getElementById('create-tipo-global');

  function resetCreateControls() {
    if (createDeptSelect) {
      createDeptSelect.value = '';
    }
    if (createTipoSelect) {
      const result = renderTipoOptions(createTipoSelect, '', '', '', '');
      updateHiddenFromSelect(createTipoSelect, createTipoGlobalInput, result.selectedGlobalId);
      createTipoSelect.dataset.currentGlobalId = '';
    }
    if (createTipoGlobalInput) {
      createTipoGlobalInput.value = '';
    }
  }

  const createModalController = attachModal(document.getElementById('incidencias-create-modal'), {
    onBeforeOpen: function() {
      if (createForm && typeof createForm.reset === 'function') {
        createForm.reset();
      }
      resetCreateControls();
    },
    onAfterOpen: function() {
      if (!createForm) {
        return;
      }
      const firstField = createForm.querySelector('input, select, textarea');
      if (firstField) {
        firstField.focus();
      }
    },
    onAfterClose: function() {
      if (createForm && typeof createForm.reset === 'function') {
        createForm.reset();
      }
      resetCreateControls();
    }
  });

  if (createDeptSelect && createTipoSelect) {
    createDeptSelect.addEventListener('change', function() {
      const deptoId = createDeptSelect.value;
      const result = renderTipoOptions(createTipoSelect, deptoId, '', '', '');
      createTipoSelect.dataset.currentGlobalId = '';
      updateHiddenFromSelect(createTipoSelect, createTipoGlobalInput, result.selectedGlobalId);
    });
  }

  if (createTipoSelect) {
    createTipoSelect.addEventListener('change', function() {
      let selectedGlobal = '';
      if (createTipoSelect.selectedIndex >= 0) {
        const opt = createTipoSelect.options[createTipoSelect.selectedIndex];
        if (opt && opt.dataset) {
          selectedGlobal = opt.dataset.globalId || '';
        }
      }
      createTipoSelect.dataset.currentGlobalId = selectedGlobal;
      updateHiddenFromSelect(createTipoSelect, createTipoGlobalInput, selectedGlobal);
    });
  }

  const createButton = document.querySelector('[data-incidencia-create-open]');
  if (createButton && createModalController) {
    createButton.addEventListener('click', function() {
      createModalController.open();
    });
  }

  document.addEventListener('keydown', function(evt) {
    if (evt.key === 'Escape' && activeController) {
      evt.preventDefault();
      activeController.close();
    }
  });

  const detailModalController = attachModal(document.getElementById('incidencias-modal'), {
    onAfterClose: function() {
      setEditable(false);
      currentId = null;
    }
  });

  if (!detailModalController) {
    return;
  }

  const modal = detailModalController.modal;
  const form = document.getElementById('incidencias-modal-form');
  const deleteForm = document.getElementById('incidencias-delete-form');
  const editButton = modal.querySelector('[data-incidencia-edit]');
  const saveButton = modal.querySelector('[data-incidencia-save]');
  const deleteButton = modal.querySelector('[data-incidencia-delete]');
  const modalTipoGlobalInput = document.getElementById('modal-tipo-global');

  const fieldMap = {
    fecha: document.getElementById('modal-fecha'),
    ticket: document.getElementById('modal-ticket'),
    cooperativa: document.getElementById('modal-cooperativa'),
    departamento: document.getElementById('modal-departamento'),
    asunto: document.getElementById('modal-asunto'),
    tipo: document.getElementById('modal-tipo'),
    prioridad: document.getElementById('modal-prioridad'),
    estado: document.getElementById('modal-estado'),
    descripcion: document.getElementById('modal-descripcion'),
    contactoNombre: document.getElementById('modal-contacto-nombre'),
    contactoCargo: document.getElementById('modal-contacto-cargo'),
    contactoTelefono: document.getElementById('modal-contacto-telefono'),
    contactoCorreo: document.getElementById('modal-contacto-correo'),
    contactoFecha: document.getElementById('modal-contacto-fecha')
  };

  let currentId = null;

  function setEditable(editable) {
    if (!fieldMap.asunto || !fieldMap.descripcion || !fieldMap.estado || !fieldMap.tipo || !fieldMap.prioridad) {
      return;
    }

    if (!editButton || !saveButton) {
      return;
    }

    fieldMap.asunto.readOnly = !editable;
    fieldMap.descripcion.readOnly = !editable;
    fieldMap.estado.disabled = !editable;
    fieldMap.tipo.disabled = !editable;
    fieldMap.prioridad.disabled = !editable;
    if (fieldMap.departamento) {
      fieldMap.departamento.disabled = !editable;
    }

    if (editable) {
      const deptoId = fieldMap.departamento ? fieldMap.departamento.value : '';
      const currentTypeId = fieldMap.tipo ? fieldMap.tipo.value : '';
      const currentTypeName = fieldMap.tipo && fieldMap.tipo.dataset ? fieldMap.tipo.dataset.currentName || '' : '';
      const currentGlobalId = fieldMap.tipo && fieldMap.tipo.dataset ? fieldMap.tipo.dataset.currentGlobalId || '' : '';
      const result = renderTipoOptions(fieldMap.tipo, deptoId, currentTypeId, currentTypeName, currentGlobalId);
      updateHiddenFromSelect(fieldMap.tipo, modalTipoGlobalInput, result.selectedGlobalId || currentGlobalId);
      if (fieldMap.tipo && fieldMap.tipo.dataset) {
        fieldMap.tipo.dataset.currentGlobalId = result.selectedGlobalId || currentGlobalId || '';
      }
    }

    if (editable) {
      editButton.setAttribute('hidden', 'hidden');
      saveButton.removeAttribute('hidden');
    } else {
      saveButton.setAttribute('hidden', 'hidden');
      editButton.removeAttribute('hidden');
    }
  }

  function fillModal(row) {
    if (!row) {
      return;
    }

    currentId = row.getAttribute('data-id');
    if (!currentId) {
      return;
    }

    const values = {
      fecha: row.getAttribute('data-fecha') || '',
      ticket: row.getAttribute('data-ticket') || '',
      cooperativa: row.getAttribute('data-cooperativa') || '',
      departamentoId: row.getAttribute('data-departamento-id') || '',
      departamentoNombre: row.getAttribute('data-departamento') || '',
      asunto: row.getAttribute('data-asunto') || '',
      tipoId: row.getAttribute('data-tipo-id') || '',
      tipo: row.getAttribute('data-tipo') || '',
      tipoGlobalId: row.getAttribute('data-tipo-global-id') || '',
      prioridad: row.getAttribute('data-prioridad') || '',
      estado: row.getAttribute('data-estado') || '',
      descripcion: row.getAttribute('data-descripcion') || '',
      contactoNombre: row.getAttribute('data-contacto-nombre') || '',
      contactoCargo: row.getAttribute('data-contacto-cargo') || '',
      contactoTelefono: row.getAttribute('data-contacto-telefono') || '',
      contactoCorreo: row.getAttribute('data-contacto-correo') || '',
      contactoFecha: row.getAttribute('data-contacto-fecha') || ''
    };

    if (fieldMap.fecha) fieldMap.fecha.value = values.fecha;
    if (fieldMap.ticket) fieldMap.ticket.value = values.ticket;
    if (fieldMap.cooperativa) fieldMap.cooperativa.value = values.cooperativa;
    if (fieldMap.departamento) {
      syncDepartamentoSelect(fieldMap.departamento, values.departamentoId, values.departamentoNombre);
    }
    if (fieldMap.asunto) fieldMap.asunto.value = values.asunto;
    if (fieldMap.descripcion) fieldMap.descripcion.value = values.descripcion;

    if (fieldMap.tipo) {
      fieldMap.tipo.dataset.currentName = values.tipo || '';
      fieldMap.tipo.dataset.currentGlobalId = values.tipoGlobalId || '';
      const tipoResult = renderTipoOptions(fieldMap.tipo, values.departamentoId, values.tipoId, values.tipo, values.tipoGlobalId);
      updateHiddenFromSelect(fieldMap.tipo, modalTipoGlobalInput, tipoResult.selectedGlobalId || values.tipoGlobalId);
      fieldMap.tipo.dataset.currentGlobalId = tipoResult.selectedGlobalId || values.tipoGlobalId || '';
    } else {
      updateHiddenFromSelect(null, modalTipoGlobalInput, values.tipoGlobalId || '');
    }

    if (fieldMap.prioridad) {
      Array.from(fieldMap.prioridad.options).forEach(function(opt) {
        opt.selected = (opt.value === values.prioridad);
      });
    }

    if (fieldMap.estado) {
      Array.from(fieldMap.estado.options).forEach(function(opt) {
        opt.selected = (opt.value === values.estado);
      });
    }

    if (fieldMap.contactoNombre) fieldMap.contactoNombre.value = values.contactoNombre;
    if (fieldMap.contactoCargo) fieldMap.contactoCargo.value = values.contactoCargo;
    if (fieldMap.contactoTelefono) fieldMap.contactoTelefono.value = values.contactoTelefono;
    if (fieldMap.contactoCorreo) fieldMap.contactoCorreo.value = values.contactoCorreo;
    if (fieldMap.contactoFecha) fieldMap.contactoFecha.value = values.contactoFecha;

    if (form) {
      form.action = '/comercial/incidencias/' + currentId;
    }
    if (deleteForm) {
      deleteForm.action = '/comercial/incidencias/' + currentId + '/eliminar';
    }

    setEditable(false);
  }

  document.querySelectorAll('[data-incidencia-open]').forEach(function(button) {
    button.addEventListener('click', function() {
      const row = button.closest('.incidencias-row');
      if (row) {
        fillModal(row);
        detailModalController.open();
      }
    });
  });

  if (editButton && saveButton) {
    editButton.addEventListener('click', function() {
      setEditable(true);
      if (fieldMap.asunto) {
        fieldMap.asunto.focus();
      }
    });
  }

  if (fieldMap.departamento) {
    fieldMap.departamento.addEventListener('change', function() {
      if (fieldMap.departamento.disabled) {
        return;
      }
      const deptoId = fieldMap.departamento.value;
      if (fieldMap.tipo && fieldMap.tipo.dataset) {
        fieldMap.tipo.dataset.currentName = '';
        fieldMap.tipo.dataset.currentGlobalId = '';
      }
      const result = renderTipoOptions(fieldMap.tipo, deptoId, '', '', '');
      updateHiddenFromSelect(fieldMap.tipo, modalTipoGlobalInput, result.selectedGlobalId);
      if (fieldMap.tipo) {
        fieldMap.tipo.value = '';
        if (fieldMap.tipo.dataset) {
          fieldMap.tipo.dataset.currentGlobalId = '';
        }
      }
    });
  }

  if (fieldMap.tipo) {
    fieldMap.tipo.addEventListener('change', function() {
      if (fieldMap.tipo.dataset) {
        fieldMap.tipo.dataset.currentGlobalId = '';
        const selectedOption = fieldMap.tipo.options[fieldMap.tipo.selectedIndex];
        if (selectedOption && selectedOption.dataset) {
          fieldMap.tipo.dataset.currentGlobalId = selectedOption.dataset.globalId || '';
        }
      }
      updateHiddenFromSelect(fieldMap.tipo, modalTipoGlobalInput, '');
    });
  }

  if (form) {
    form.addEventListener('submit', function() {
      setEditable(false);
    });
  }

  if (deleteButton && deleteForm) {
    deleteButton.addEventListener('click', function() {
      if (!currentId) {
        return;
      }
    const confirmar = window.confirm('¿Deseas eliminar esta gestión?');
      if (confirmar) {
        deleteForm.submit();
      }
    });
  }
})();
