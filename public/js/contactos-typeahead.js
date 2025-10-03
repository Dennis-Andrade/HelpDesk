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
  const button = document.querySelector('[data-contact-form-toggle]');
  if (!button) { return; }

  const targetId = button.getAttribute('aria-controls');
  const panel = targetId ? document.getElementById(targetId) : null;
  if (!panel) { return; }

  const initiallyExpanded = button.getAttribute('aria-expanded') === 'true';
  panel.hidden = !initiallyExpanded;
  panel.setAttribute('aria-hidden', String(!initiallyExpanded));

  button.addEventListener('click', () => {
    const expanded = button.getAttribute('aria-expanded') === 'true';
    const nextState = !expanded;
    button.setAttribute('aria-expanded', String(nextState));
    panel.hidden = !nextState;
    panel.setAttribute('aria-hidden', String(!nextState));
  });
})();
