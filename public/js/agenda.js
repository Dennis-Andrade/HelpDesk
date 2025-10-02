(function () {
  'use strict';

  const modal = document.getElementById('agenda-modal');
  const backdrop = document.getElementById('agenda-modal-backdrop');
  const statusForm = modal ? modal.querySelector('[data-agenda-status-form]') : null;
  const cancelForm = modal ? modal.querySelector('[data-agenda-cancel-form]') : null;
  const closeButton = modal ? modal.querySelector('[data-agenda-close]') : null;

  function updateField(name, value) {
    if (!modal) { return; }
    const el = modal.querySelector('[data-agenda-field="' + name + '"]');
    if (el) {
      el.textContent = value && value !== '' ? value : '—';
    }
  }

  function toggleFooter(state) {
    if (!statusForm || !cancelForm) { return; }
    const lower = (state || '').toLowerCase();
    const isPendiente = lower === 'pendiente' || lower === '';
    statusForm.hidden = !isPendiente;
    cancelForm.hidden = !isPendiente;
  }

  function openModalForRow(row) {
    if (!modal || !row) { return; }
    const id = row.getAttribute('data-agenda-id');
    updateField('fecha', row.getAttribute('data-agenda-fecha'));
    updateField('titulo', row.getAttribute('data-agenda-titulo'));
    updateField('entidad', row.getAttribute('data-agenda-entidad'));
    updateField('nombre', row.getAttribute('data-agenda-nombre'));
    updateField('telefono', row.getAttribute('data-agenda-telefono'));
    updateField('correo', row.getAttribute('data-agenda-correo'));
    updateField('cargo', row.getAttribute('data-agenda-cargo'));
    updateField('nota', row.getAttribute('data-agenda-nota'));
    const estado = row.getAttribute('data-agenda-estado') || 'Pendiente';
    updateField('estado', estado);
    toggleFooter(estado);

    if (statusForm && typeof id === 'string') {
      statusForm.setAttribute('action', '/comercial/agenda/' + encodeURIComponent(id) + '/estado');
    }
    if (cancelForm && typeof id === 'string') {
      cancelForm.setAttribute('action', '/comercial/agenda/' + encodeURIComponent(id) + '/estado');
    }

    modal.removeAttribute('hidden');
    if (backdrop) {
      backdrop.removeAttribute('hidden');
    }
    if (closeButton) {
      closeButton.focus();
    }
    document.addEventListener('keydown', handleKeydown);
  }

  function closeModal() {
    if (!modal) { return; }
    modal.setAttribute('hidden', 'hidden');
    if (backdrop) {
      backdrop.setAttribute('hidden', 'hidden');
    }
    document.removeEventListener('keydown', handleKeydown);
  }

  function handleKeydown(event) {
    if (event.key === 'Escape') {
      closeModal();
    }
  }

  function bindOpenButtons() {
    const buttons = document.querySelectorAll('[data-agenda-open]');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        const row = btn.closest('tr');
        if (row) {
          openModalForRow(row);
        }
      });
    });
  }

  function bindCloseControls() {
    if (closeButton) {
      closeButton.addEventListener('click', closeModal);
    }
    if (backdrop) {
      backdrop.addEventListener('click', closeModal);
    }
  }

  function autoDismissToasts() {
    const toasts = document.querySelectorAll('.agenda-page__toast');
    toasts.forEach(function (toast) {
      setTimeout(function () {
        toast.classList.add('agenda-toast-hide');
        setTimeout(function () { toast.remove(); }, 400);
      }, 10000);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindOpenButtons();
    bindCloseControls();
    autoDismissToasts();
  });
})();
