/* global fetch */
(function () {
  'use strict';

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (response) {
      if (!response.ok) {
        throw new Error('Error de red');
      }
      return response.json();
    });
  }

  function renderDetalle(container, data) {
    if (!container || !data) {
      return;
    }
    var html = '<dl class="inventario-detalle">';
    html += '<div><dt>Nombre</dt><dd>' + escapeHtml(data.nombre || '') + '</dd></div>';
    html += '<div><dt>Código</dt><dd>' + escapeHtml(data.codigo || '') + '</dd></div>';
    if (data.descripcion) {
      html += '<div><dt>Descripción</dt><dd>' + escapeHtml(data.descripcion) + '</dd></div>';
    }
    html += '<div><dt>Estado</dt><dd>' + escapeHtml(data.estado || '') + '</dd></div>';
    if (data.responsable) {
      html += '<div><dt>Responsable</dt><dd>' + escapeHtml(data.responsable);
      if (data.responsable_contacto) {
        html += ' · ' + escapeHtml(data.responsable_contacto);
      }
      html += '</dd></div>';
    }
    if (data.fecha_entrega) {
      html += '<div><dt>Fecha de entrega</dt><dd>' + escapeHtml(formatDate(data.fecha_entrega)) + '</dd></div>';
    }
    if (data.serie) {
      html += '<div><dt>Serie</dt><dd>' + escapeHtml(data.serie) + '</dd></div>';
    }
    if (data.marca || data.modelo) {
      html += '<div><dt>Marca / modelo</dt><dd>' + escapeHtml([data.marca, data.modelo].filter(Boolean).join(' · ')) + '</dd></div>';
    }
    if (data.comentarios) {
      html += '<div><dt>Comentarios</dt><dd>' + escapeHtml(data.comentarios) + '</dd></div>';
    }
    if (data.documento_path) {
      html += '<div><dt>Documento</dt><dd><a href="/storage/' + escapeHtml(data.documento_path) + '" target="_blank" rel="noopener">Ver archivo</a></dd></div>';
    }
    html += '</dl>';
    container.innerHTML = html;
  }

  function escapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value;
    return div.innerHTML;
  }

  function formatDate(value) {
    var date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return value;
    }
    return date.toLocaleDateString();
  }

  function setupModal() {
    var overlay = document.querySelector('[data-inventario-modal]');
    var content = overlay ? overlay.querySelector('[data-inventario-contenido]') : null;
    var closeBtn = overlay ? overlay.querySelector('[data-inventario-close]') : null;
    if (!overlay || !content) {
      return;
    }

    function closeModal() {
      overlay.setAttribute('hidden', 'hidden');
      overlay.classList.remove('is-active');
      document.body.classList.remove('modal-open');
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', closeModal);
    }
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        closeModal();
      }
    });

    document.querySelectorAll('[data-inventario-detalle]').forEach(function (button) {
      button.addEventListener('click', function () {
        var id = button.getAttribute('data-equipo-id');
        if (!id) {
          return;
        }
        overlay.removeAttribute('hidden');
        overlay.classList.add('is-active');
        document.body.classList.add('modal-open');
        content.innerHTML = '<p>Cargando...</p>';

        fetchJson('/contabilidad/inventario/' + encodeURIComponent(id))
          .then(function (response) {
            if (!response || !response.ok) {
              throw new Error('La respuesta no es válida');
            }
            renderDetalle(content, response.item);
          })
          .catch(function () {
            content.innerHTML = '<p>No se pudo cargar la información del equipo.</p>';
          });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', setupModal);
})();
