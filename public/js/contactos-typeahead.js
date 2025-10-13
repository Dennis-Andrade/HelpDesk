(function(){
  const input = document.getElementById('contactos-search-input');
  const box   = document.getElementById('contactos-search-suggestions');
  if (!input || !box) { return; }

  const form      = input.form;
  const minChars  = parseInt(box.getAttribute('data-min-chars') || '3', 10);
  let controller  = null;
  let activeIndex = -1;
  let lastQuery   = '';

  function clearSuggestions() {
    box.innerHTML = '';
    box.hidden = true;
    box.setAttribute('aria-hidden', 'true');
    input.setAttribute('aria-expanded', 'false');
    activeIndex = -1;
  }

  function highlight(index) {
    const buttons = box.querySelectorAll('button[data-term]');
    buttons.forEach((btn, idx) => {
      if (idx === index) {
        btn.classList.add('is-active');
        btn.setAttribute('aria-selected', 'true');
      } else {
        btn.classList.remove('is-active');
        btn.setAttribute('aria-selected', 'false');
      }
    });
    activeIndex = index;
  }

  function selectSuggestion(button) {
    if (!button) { return; }
    const term = button.getAttribute('data-term') || '';
    if (!term) { return; }
    input.value = term;
    clearSuggestions();
    if (form) {
      form.requestSubmit ? form.requestSubmit() : form.submit();
    }
  }

  function renderSuggestions(items) {
    clearSuggestions();
    if (!items.length) { return; }

    const frag = document.createDocumentFragment();
    items.forEach((item, index) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ent-search__suggestion';
      btn.textContent = item.label || item.term || '';
      btn.setAttribute('data-term', item.term || '');
      btn.setAttribute('role', 'option');
      btn.setAttribute('aria-selected', 'false');
      btn.addEventListener('mousedown', (ev) => {
        ev.preventDefault();
        selectSuggestion(btn);
      });
      frag.appendChild(btn);
    });

    box.appendChild(frag);
    box.hidden = false;
    box.setAttribute('aria-hidden', 'false');
    input.setAttribute('aria-expanded', 'true');
    highlight(-1);
  }

  async function fetchSuggestions(query) {
    if (controller) {
      controller.abort();
    }
    controller = new AbortController();

    try {
      const response = await fetch('/comercial/contactos/sugerencias?q=' + encodeURIComponent(query), {
        headers: { 'Accept': 'application/json' },
        signal: controller.signal,
      });
      if (!response.ok) {
        throw new Error('Solicitud fallida');
      }
      const data = await response.json();
      const items = Array.isArray(data.items) ? data.items : [];
      renderSuggestions(items);
    } catch (error) {
      if (error.name === 'AbortError') { return; }
      clearSuggestions();
    }
  }

  input.setAttribute('role', 'combobox');
  input.setAttribute('aria-autocomplete', 'list');
  input.setAttribute('aria-expanded', 'false');
  input.setAttribute('aria-controls', box.id);

  input.addEventListener('input', (event) => {
    const value = (event.target.value || '').trim();
    if (value.length < minChars) {
      clearSuggestions();
      lastQuery = value;
      return;
    }
    if (value === lastQuery) {
      return;
    }
    lastQuery = value;
    fetchSuggestions(value);
  });

  input.addEventListener('keydown', (event) => {
    if (box.hidden) { return; }
    const buttons = box.querySelectorAll('button[data-term]');
    if (!buttons.length) { return; }

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        if (activeIndex < buttons.length - 1) {
          highlight(activeIndex + 1);
        } else {
          highlight(0);
        }
        break;
      case 'ArrowUp':
        event.preventDefault();
        if (activeIndex > 0) {
          highlight(activeIndex - 1);
        } else {
          highlight(buttons.length - 1);
        }
        break;
      case 'Enter':
        if (activeIndex >= 0 && buttons[activeIndex]) {
          event.preventDefault();
          selectSuggestion(buttons[activeIndex]);
        }
        break;
      case 'Escape':
        clearSuggestions();
        break;
      default:
        break;
    }
  });

  input.addEventListener('blur', () => {
    setTimeout(() => clearSuggestions(), 120);
  });
})();

(function(){
  const toggles = document.querySelectorAll('[data-contact-toggle]');
  if (!toggles.length) { return; }

  toggles.forEach((button) => {
    const targetId = button.getAttribute('aria-controls');
    const panel = targetId ? document.getElementById(targetId) : null;
    if (!panel) { return; }

    button.addEventListener('click', () => {
      const expanded = button.getAttribute('aria-expanded') === 'true';
      const nextState = !expanded;
      button.setAttribute('aria-expanded', String(nextState));
      panel.hidden = !nextState;
      panel.setAttribute('aria-hidden', String(!nextState));
    });
  });
})();

(function(){
  const registry = new Map();

  function anyModalVisible() {
    for (const modal of registry.values()) {
      if (modal.isVisible()) {
        return true;
      }
    }
    return false;
  }

  function setupModal(modalElement) {
    const dialog = modalElement.querySelector('[data-modal-dialog]');
    const closeButtons = modalElement.querySelectorAll('[data-modal-close]');
    const cancelButtons = modalElement.querySelectorAll('[data-modal-cancel]');
    const form = modalElement.querySelector('form');
    const focusTarget = modalElement.querySelector('[data-focus-initial]');
    let lastFocusedElement = null;

    function isVisible() {
      return modalElement.getAttribute('aria-hidden') === 'false';
    }

    function show(trigger) {
      lastFocusedElement = trigger instanceof HTMLElement
        ? trigger
        : (document.activeElement instanceof HTMLElement ? document.activeElement : null);

      modalElement.hidden = false;
      modalElement.setAttribute('aria-hidden', 'false');
      document.body.classList.add('is-modal-open');

      if (dialog instanceof HTMLElement) {
        dialog.focus();
      }

      requestAnimationFrame(() => {
        if (focusTarget instanceof HTMLElement) {
          focusTarget.focus();
        }
      });
    }

    function hide() {
      modalElement.hidden = true;
      modalElement.setAttribute('aria-hidden', 'true');

      if (form instanceof HTMLFormElement && modalElement.getAttribute('data-modal-reset') !== 'false') {
        form.reset();
      }

      if (!anyModalVisible()) {
        document.body.classList.remove('is-modal-open');
      }

      if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
        lastFocusedElement.focus();
      }
    }

    closeButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        if (isVisible()) {
          hide();
        }
      });
    });

    cancelButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        if (isVisible()) {
          hide();
        }
      });
    });

    modalElement.addEventListener('click', (event) => {
      if (event.target === modalElement && isVisible()) {
        hide();
      }
    });

    return {
      show,
      hide,
      isVisible,
    };
  }

  document.querySelectorAll('[data-modal]').forEach((modalElement) => {
    const instance = setupModal(modalElement);
    const key = modalElement.id || modalElement.getAttribute('data-modal-id');
    if (!key) { return; }
    registry.set(key, instance);
  });

  document.querySelectorAll('[data-modal-open]').forEach((button) => {
    const targetId = button.getAttribute('data-modal-open');
    if (!targetId) { return; }
    const modal = registry.get(targetId);
    if (!modal) { return; }

    button.addEventListener('click', (event) => {
      event.preventDefault();
      modal.show(button);
    });
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      let handled = false;
      registry.forEach((modal) => {
        if (modal.isVisible()) {
          modal.hide();
          handled = true;
        }
      });
      if (handled) {
        event.preventDefault();
      }
    }
  });

  window.contactModals = {
    get(id) {
      return registry.get(id) || null;
    },
  };
})();

(function(){
  const editModalId = 'contacto-editar-modal';
  const modal = document.getElementById(editModalId);
  if (!modal) { return; }

  const form = modal.querySelector('[data-contact-edit-form]');
  const entidad = modal.querySelector('#modal-editar-contacto-entidad');
  const nombre = modal.querySelector('#modal-editar-contacto-nombre');
  const titulo = modal.querySelector('#modal-editar-contacto-titulo');
  const cargo = modal.querySelector('#modal-editar-contacto-cargo');
  const telefono = modal.querySelector('#modal-editar-contacto-telefono');
  const correo = modal.querySelector('#modal-editar-contacto-correo');
  const nota = modal.querySelector('#modal-editar-contacto-nota');
  const fecha = modal.querySelector('#modal-editar-contacto-fecha');

  function setValue(field, value) {
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      field.value = value || '';
    }
  }

  document.querySelectorAll('[data-contact-edit]').forEach((button) => {
    button.addEventListener('click', (event) => {
      if (!(form instanceof HTMLFormElement)) { return; }

      const contactId = button.getAttribute('data-contact-id') || '';
      if (contactId === '') { return; }

      form.setAttribute('action', '/comercial/contactos/' + encodeURIComponent(contactId));
      setValue(entidad, button.getAttribute('data-contact-entidad') || '');
      setValue(nombre, button.getAttribute('data-contact-nombre') || '');
      setValue(titulo, button.getAttribute('data-contact-titulo') || '');
      setValue(cargo, button.getAttribute('data-contact-cargo') || '');
      setValue(telefono, button.getAttribute('data-contact-telefono') || '');
      setValue(correo, button.getAttribute('data-contact-correo') || '');
      setValue(nota, button.getAttribute('data-contact-nota') || '');
      setValue(fecha, button.getAttribute('data-contact-fecha') || '');

    }, { capture: true });
  });
})();
