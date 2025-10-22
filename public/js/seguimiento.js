(function () {
  function scheduleToastDismiss(toast, delay) {
    if (!toast) {
      return;
    }
    var timer = setTimeout(function () {
      toast.style.transition = 'opacity .4s ease';
      toast.style.opacity = '0';
      setTimeout(function () {
        if (toast && toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 420);
      clearTimeout(timer);
    }, delay);
  }

  function autoDismissToast() {
    var toast = document.getElementById('ent-toast');
    if (toast && toast.textContent && toast.textContent.trim() !== '') {
      scheduleToastDismiss(toast, 5200);
    }
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
      if (datalist) {
        datalist.innerHTML = '';
      }
    }

    function populate(options) {
      if (!datalist) {
        return;
      }
      datalist.innerHTML = '';
      options.forEach(function (item) {
        if (!item) { return; }
        var value = '';
        if (item.codigo && item.codigo !== '') {
          value = item.codigo;
        } else if (item.ticket_id) {
          value = 'Ticket #' + item.ticket_id;
        } else if (item.descripcion) {
          value = item.descripcion;
        }
        if (!value) { return; }
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
      fetch('/comercial/eventos/tickets/buscar?q=' + encodeURIComponent(normalized), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Error');
          }
          return response.json();
        })
        .then(function (payload) {
          if (!payload || !Array.isArray(payload.items)) {
            clearOptions();
            return;
          }
          populate(payload.items.slice(0, 10));
        })
        .catch(function () {
          clearOptions();
        });
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
    var initialState = advanced && advanced.dataset && advanced.dataset.initiallyOpen === 'true';
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
        toggle.setAttribute('aria-expanded', 'true');
        if (openLabel) { openLabel.setAttribute('hidden', ''); }
        if (closeLabel) { closeLabel.removeAttribute('hidden'); }
      } else {
        advanced.hidden = true;
        advanced.setAttribute('hidden', '');
        advanced.style.display = 'none';
        toggle.setAttribute('aria-expanded', 'false');
        if (openLabel) { openLabel.removeAttribute('hidden'); }
        if (closeLabel) { closeLabel.setAttribute('hidden', ''); }
      }
    }

    if (toggle && advanced) {
      var hasValue = false;
      advanced.querySelectorAll('input, select').forEach(function (field) {
        if (field.value && field.value !== '') {
          hasValue = true;
        }
      });
      setState(hasValue || initialState);
      toggle.addEventListener('click', function (event) {
        event.preventDefault();
        var expanded = toggle.getAttribute('aria-expanded') === 'true';
        setState(!expanded);
      });
    }

    setupTicketFilterSearch(form);
  }

  function setupEntityModal() {
    var modal = document.querySelector('[data-seguimiento-entity-modal]');
    if (!modal) {
      return;
    }
    var closeButtons = modal.querySelectorAll('[data-seguimiento-entity-close]');
    var list = modal.querySelector('[data-seguimiento-entity-list]');
    var empty = modal.querySelector('[data-seguimiento-entity-empty]');
    var title = modal.querySelector('[data-seguimiento-entity-title]');
    var subtitle = modal.querySelector('[data-seguimiento-entity-subtitle]');

    function closeModal() {
      modal.setAttribute('hidden', '');
      document.body.classList.remove('ent-modal-open');
    }

    closeButtons.forEach(function (btn) {
      btn.addEventListener('click', function (event) {
        event.preventDefault();
        closeModal();
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
        closeModal();
      }
    });

    function renderItems(items) {
      if (!list || !empty) {
        return;
      }
      list.innerHTML = '';
      if (!items.length) {
        empty.removeAttribute('hidden');
        return;
      }
      empty.setAttribute('hidden', 'true');
      items.forEach(function (item) {
        var li = document.createElement('li');
        var heading = document.createElement('h3');
        heading.textContent = (item.tipo || 'Seguimiento');
        li.appendChild(heading);

        if (item.fecha) {
          var date = document.createElement('p');
          date.textContent = 'Fecha: ' + item.fecha;
          li.appendChild(date);
        }
        if (item.descripcion) {
          var desc = document.createElement('p');
          desc.textContent = item.descripcion;
          li.appendChild(desc);
        }
        if (item.ticket) {
          var ticket = document.createElement('p');
          ticket.textContent = 'Ticket: ' + item.ticket;
          li.appendChild(ticket);
        }
        if (item.usuario) {
          var usuario = document.createElement('p');
          usuario.textContent = 'Registrado por: ' + item.usuario;
          li.appendChild(usuario);
        }
        list.appendChild(li);
      });
    }

    function openModal(entityId, entityName) {
      if (!entityId) { return; }
      modal.removeAttribute('hidden');
      document.body.classList.add('ent-modal-open');
      if (title) {
        title.textContent = 'Historial de ' + entityName;
      }
      if (subtitle) {
        subtitle.textContent = 'Cargando historial…';
      }
      renderItems([]);
      fetch('/comercial/eventos/entidades/' + encodeURIComponent(entityId), {
        headers: { 'Accept': 'application/json' }
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Error');
          }
          return response.json();
        })
        .then(function (payload) {
          if (subtitle) {
            subtitle.textContent = (payload.items && payload.items.length) ? payload.items.length + ' gestiones registradas' : 'Sin gestiones registradas';
          }
          var items = Array.isArray(payload.items) ? payload.items : [];
          renderItems(items);
        })
        .catch(function () {
          if (subtitle) {
            subtitle.textContent = 'No se pudo cargar el historial.';
          }
          renderItems([]);
        });
    }

    document.querySelectorAll('[data-seguimiento-entity-modal-trigger]').forEach(function (button) {
      button.addEventListener('click', function () {
        var entityId = button.getAttribute('data-entity-id');
        var entityName = button.getAttribute('data-entity-name') || 'Entidad';
        openModal(entityId, entityName);
      });
    });
  }

  autoDismissToast();

  var resetButton = document.querySelector('[data-action="seguimiento-reset"]');
  if (resetButton) {
    resetButton.addEventListener('click', function (event) {
      event.preventDefault();
      handleReset(resetButton);
    });
  }

  setupFilters(document.querySelector('.seguimiento-filters'));
  setupEntityModal();
})();
