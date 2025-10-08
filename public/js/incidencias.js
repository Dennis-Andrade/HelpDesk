(function() {
  const body = document.body;
  let activeController = null;

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
  const createModalController = attachModal(document.getElementById('incidencias-create-modal'), {
    onBeforeOpen: function() {
      if (createForm && typeof createForm.reset === 'function') {
        createForm.reset();
      }
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
    }
  });

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

  const fieldMap = {
    fecha: document.getElementById('modal-fecha'),
    ticket: document.getElementById('modal-ticket'),
    cooperativa: document.getElementById('modal-cooperativa'),
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
      asunto: row.getAttribute('data-asunto') || '',
      tipo: row.getAttribute('data-tipo') || '',
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
    if (fieldMap.asunto) fieldMap.asunto.value = values.asunto;
    if (fieldMap.descripcion) fieldMap.descripcion.value = values.descripcion;

    if (fieldMap.tipo) {
      const opciones = Array.from(fieldMap.tipo.options);
      const coincide = opciones.some(function(opt) {
        if (opt.value === values.tipo) {
          opt.selected = true;
          return true;
        }
        return false;
      });
      if (!coincide && opciones.length > 0) {
        opciones[0].selected = true;
      }
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
      const confirmar = window.confirm('¿Deseas eliminar esta incidencia?');
      if (confirmar) {
        deleteForm.submit();
      }
    });
  }
})();
