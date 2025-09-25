(function () {
    'use strict';

    const modal = document.getElementById('agenda-modal');
    const modalBody = document.getElementById('agenda-modal-body');
    const closeBtn = modal ? modal.querySelector('.agenda-modal__close') : null;
    const backdrop = document.querySelector('[data-agenda-modal-backdrop]');
    const feedback = document.getElementById('agenda-feedback');
    let lastFocused = null;
    let trapHandler = null;

    function setFeedback(message, isError) {
        if (!feedback) {
            return;
        }
        feedback.textContent = message;
        feedback.classList.toggle('agenda-feedback--error', Boolean(isError && message));
    }

    function getFocusableElements() {
        if (!modal) {
            return [];
        }
        return Array.from(
            modal.querySelectorAll(
                'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
            )
        ).filter(function (el) { return !el.hasAttribute('hidden'); });
    }

    function handleTrap(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        const focusable = getFocusableElements();
        if (focusable.length === 0) {
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function openModal(content) {
        if (!modal || !modalBody) {
            return;
        }
        modalBody.innerHTML = '';
        modalBody.appendChild(content);
        modal.removeAttribute('hidden');
        if (backdrop) {
            backdrop.removeAttribute('hidden');
        }
        lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        trapHandler = handleTrap;
        document.addEventListener('keydown', trapHandler);
        if (closeBtn instanceof HTMLElement) {
            closeBtn.focus();
        }
    }

    function closeModal() {
        if (!modal || !modalBody) {
            return;
        }
        modalBody.innerHTML = '';
        modal.setAttribute('hidden', 'hidden');
        if (backdrop) {
            backdrop.setAttribute('hidden', 'hidden');
        }
        if (trapHandler) {
            document.removeEventListener('keydown', trapHandler);
            trapHandler = null;
        }
        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
        lastFocused = null;
    }

    function renderDetalle(data) {
        const fragment = document.createDocumentFragment();
        const list = document.createElement('dl');
        list.className = 'agenda-modal__list';

        function addRow(label, value) {
            const dt = document.createElement('dt');
            dt.textContent = label;
            const dd = document.createElement('dd');
            dd.textContent = value !== '' ? value : '—';
            list.appendChild(dt);
            list.appendChild(dd);
        }

        addRow('Entidad', data.cooperativa ? String(data.cooperativa) : '');
        addRow('Título', data.titulo ? String(data.titulo) : '');
        addRow('Descripción', data.descripcion ? String(data.descripcion) : '');
        addRow('Fecha', data.fecha_evento ? String(data.fecha_evento) : '');
        addRow('Teléfono', data.telefono_contacto ? String(data.telefono_contacto) : '');
        addRow('Email', data.email_contacto ? String(data.email_contacto) : '');
        addRow('Estado', data.estado ? String(data.estado) : '');

        fragment.appendChild(list);
        return fragment;
    }

    async function handleViewClick(event) {
        const button = event.currentTarget;
        const id = button && button.getAttribute('data-agenda-view');
        if (!id) {
            return;
        }
        setFeedback('Cargando detalle…', false);
        try {
            const response = await fetch('/comercial/agenda/' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) {
                throw new Error('Respuesta inválida');
            }
            const data = await response.json();
            setFeedback('', false);
            openModal(renderDetalle(data));
        } catch (error) {
            console.error(error);
            setFeedback('No se pudo obtener el detalle del evento.', true);
        }
    }

    function bindViewButtons() {
        const buttons = document.querySelectorAll('[data-agenda-view]');
        buttons.forEach(function (button) {
            button.addEventListener('click', handleViewClick);
        });
    }

    function bindClose() {
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        if (backdrop) {
            backdrop.addEventListener('click', closeModal);
        }
    }

    function bindDeleteConfirm() {
        const deleteButtons = document.querySelectorAll('[data-agenda-delete]');
        deleteButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                const ok = window.confirm('¿Deseas eliminar este evento?');
                if (!ok) {
                    event.preventDefault();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!modal) {
            return;
        }
        bindViewButtons();
        bindClose();
        bindDeleteConfirm();
    });
})();
