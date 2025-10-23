(function () {
  'use strict';

  function fetchCantones(url, provinciaId) {
    if (!provinciaId) {
      return Promise.resolve([]);
    }
    var endpoint = url + (url.indexOf('?') === -1 ? '?' : '&') + 'provincia_id=' + encodeURIComponent(provinciaId);
    return fetch(endpoint, { headers: { 'Accept': 'application/json' } })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Solicitud inválida');
        }
        return response.json();
      })
      .catch(function () {
        return [];
      });
  }

  function syncSelectToText(select, textInput) {
    if (!select || !textInput) {
      return;
    }
    var selected = select.options[select.selectedIndex];
    if (selected && selected.value !== '') {
      textInput.value = selected.textContent || '';
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('[data-facturacion-form]');
    if (!form) { return; }

    var provinciaSelect = form.querySelector('#fact-provincia');
    var cantonSelect = form.querySelector('#fact-canton');
    var provinciaTexto = form.querySelector('#fact-provincia-texto');
    var cantonTexto = form.querySelector('#fact-canton-texto');

    if (provinciaSelect) {
      syncSelectToText(provinciaSelect, provinciaTexto);
      provinciaSelect.addEventListener('change', function () {
        syncSelectToText(provinciaSelect, provinciaTexto);
        var url = provinciaSelect.dataset.cantonesUrl;
        if (!url || !cantonSelect) {
          return;
        }
        cantonSelect.innerHTML = '<option value="">Seleccione</option>';
        fetchCantones(url, provinciaSelect.value).then(function (cantones) {
          if (!Array.isArray(cantones)) {
            return;
          }
          cantones.forEach(function (canton) {
            if (!canton || canton.id === undefined) { return; }
            var option = document.createElement('option');
            option.value = String(canton.id);
            option.textContent = canton.nombre || ('Cantón ' + canton.id);
            cantonSelect.appendChild(option);
          });
          if (cantonTexto) {
            cantonTexto.value = '';
          }
        });
      });
    }

    if (cantonSelect) {
      syncSelectToText(cantonSelect, cantonTexto);
      cantonSelect.addEventListener('change', function () {
        syncSelectToText(cantonSelect, cantonTexto);
      });
    }
  });
})();
