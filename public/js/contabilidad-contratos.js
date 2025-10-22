(function () {
  'use strict';

  var IVA_RATE = 0.15;
  var IVA_PERCENT_DEFAULT = 15;

  function toNumber(value) {
    if (value === null || value === undefined) {
      return 0;
    }
    var str = String(value).replace(/,/g, '.');
    var num = parseFloat(str);
    return isNaN(num) ? 0 : num;
  }

  function format(value) {
    return Number.isFinite(value) ? value.toFixed(2) : '';
  }

  function showToast(message, variant) {
    var toast = document.getElementById('ent-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'ent-toast';
      toast.className = 'ent-toast';
      document.body.appendChild(toast);
    }
    toast.textContent = message || '';
    toast.style.background = variant === 'error' ? '#dc2626' : '';
    toast.style.opacity = '1';
    toast.style.display = 'block';
    setTimeout(function () {
      toast.style.transition = 'opacity .3s ease';
      toast.style.opacity = '0';
      setTimeout(function () {
        if (toast && toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 320);
    }, 3800);
  }

  function populateHistorialStates(select) {
    if (!select) { return; }
    var states = Array.isArray(window.contabHistorialEstados) ? window.contabHistorialEstados : [];
    select.innerHTML = '';
    states.forEach(function (state) {
      var option = document.createElement('option');
      option.value = state;
      option.textContent = state.charAt(0).toUpperCase() + state.slice(1);
      select.appendChild(option);
    });
  }

  function setupContractForms() {
    var forms = document.querySelectorAll('[data-contrato-form]');
    forms.forEach(function (form) {
      var baseInput = form.querySelector('[data-contract-base]');
      var ivaInput = form.querySelector('[data-contract-iva]');
      var ivaRateInput = form.querySelector('[data-contract-iva-rate]');
      var totalInput = form.querySelector('[data-contract-total]');

      function ensureRate() {
        if (!ivaRateInput) {
          return IVA_RATE;
        }
        var percent = toNumber(ivaRateInput.value);
        if (percent <= 0) {
          percent = IVA_RATE * 100;
        }
        if (percent > 100) {
          percent = 100;
        }
        return percent / 100;
      }

      function recalc(forceIva) {
        var base = toNumber(baseInput ? baseInput.value : 0);
        if (base <= 0) {
          if (forceIva && ivaInput) {
            ivaInput.value = '';
          }
          if (totalInput) {
            totalInput.value = '';
          }
          return;
        }

        if (forceIva && ivaInput) {
          var rate = ensureRate();
          ivaInput.value = format(base * rate);
        }

        if (totalInput) {
          var ivaValue = toNumber(ivaInput ? ivaInput.value : 0);
          totalInput.value = format(base + ivaValue);
        }
      }

      if (baseInput) {
        baseInput.addEventListener('input', function () { recalc(true); });
        baseInput.addEventListener('change', function () { recalc(true); });
      }
      if (ivaRateInput) {
        ivaRateInput.addEventListener('input', function () { recalc(true); });
        ivaRateInput.addEventListener('change', function () { recalc(true); });
      }
      if (ivaInput) {
        ivaInput.addEventListener('input', function () { recalc(false); });
        ivaInput.addEventListener('change', function () { recalc(false); });
      }

      recalc(true);

      var chipInputs = form.querySelectorAll('.chip input[type="checkbox"]');
      chipInputs.forEach(function (checkbox) {
        var chip = checkbox.closest('.chip');
        var sync = function () {
          if (chip) {
            chip.classList.toggle('is-checked', checkbox.checked);
          }
        };
        sync();
        checkbox.addEventListener('change', sync);
      });

      var activeToggle = form.querySelector('[data-contract-active]');
      if (activeToggle) {
        var wrapper = activeToggle.closest('.switch-toggle');
        var syncActive = function () {
          if (wrapper) {
            wrapper.classList.toggle('is-active', activeToggle.checked);
          }
        };
        activeToggle.addEventListener('change', syncActive);
        syncActive();
      }
    });
  }

  function setupHistorialModal() {
    var overlay = document.querySelector('[data-historial-overlay]');
    var modal = overlay ? overlay.querySelector('[data-historial-modal]') : null;
    if (!overlay || !modal) {
      return;
    }

    var closeBtn = modal.querySelector('[data-historial-close]');
    var listBody = modal.querySelector('[data-historial-list]');
    var contratoLabel = modal.querySelector('[data-historial-contrato]');
    var form = modal.querySelector('[data-historial-form]');
    var estadosSelect = form ? form.querySelector('[data-historial-estado]') : null;
    var baseInput = form ? form.querySelector('[data-historial-base]') : null;
    var ivaRateInput = form ? form.querySelector('[data-historial-iva-rate]') : null;
    var ivaInput = form ? form.querySelector('[data-historial-iva]') : null;
    var totalInput = form ? form.querySelector('[data-historial-total]') : null;
    var cooperativaHidden = form ? form.querySelector('[data-historial-cooperativa]') : null;
    var contratoHidden = form ? form.querySelector('[data-historial-contrato-id]') : null;

    populateHistorialStates(estadosSelect);

    if (overlay) {
      overlay.setAttribute('hidden', 'hidden');
      overlay.style.display = 'none';
    }

    var currentContratoId = '';
    var currentCooperativaId = '';

    function ensureRateValue() {
      if (!ivaRateInput) {
        return IVA_RATE;
      }
      var ratePercent = toNumber(ivaRateInput.value);
      if (ratePercent <= 0) {
        ivaRateInput.value = String(IVA_PERCENT_DEFAULT);
        ratePercent = IVA_PERCENT_DEFAULT;
      } else if (ratePercent > 100) {
        ivaRateInput.value = '100';
        ratePercent = 100;
      }
      return ratePercent / 100;
    }

    function refreshTotals(forceIva) {
      var rate = ensureRateValue();
      var base = toNumber(baseInput ? baseInput.value : 0);
      if (base <= 0) {
        if (forceIva && ivaInput) {
          ivaInput.value = '';
        }
        if (totalInput) {
          totalInput.value = '';
        }
        return;
      }

      if (forceIva && ivaInput) {
        ivaInput.value = format(base * rate);
      }

      if (totalInput) {
        var ivaAmount = toNumber(ivaInput ? ivaInput.value : 0);
        totalInput.value = format(base + ivaAmount);
      }
    }

    if (baseInput) {
      baseInput.addEventListener('input', function () {
        refreshTotals(true);
      });
      baseInput.addEventListener('change', function () {
        refreshTotals(true);
      });
    }
    if (ivaRateInput) {
      ivaRateInput.addEventListener('input', function () {
        refreshTotals(true);
      });
      ivaRateInput.addEventListener('change', function () {
        refreshTotals(true);
      });
    }
    if (ivaInput) {
      ivaInput.addEventListener('input', function () {
        refreshTotals(false);
      });
      ivaInput.addEventListener('change', function () {
        refreshTotals(false);
      });
    }

    function closeModal() {
      overlay.setAttribute('hidden', 'hidden');
      overlay.style.display = 'none';
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        closeModal();
      });
    }
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        closeModal();
      }
    });

    function renderRows(items) {
      if (!listBody) { return; }
      listBody.innerHTML = '';
      if (!Array.isArray(items) || items.length === 0) {
        var empty = document.createElement('tr');
        var cell = document.createElement('td');
        cell.colSpan = 6;
        cell.textContent = 'Aún no se registran pagos para este contrato.';
        empty.appendChild(cell);
        listBody.appendChild(empty);
        return;
      }

      items.forEach(function (item) {
        var row = document.createElement('tr');

        function td(text) {
          var cell = document.createElement('td');
          cell.textContent = text;
          return cell;
        }

        row.appendChild(td(item.periodo || '—'));
        row.appendChild(td(item.fecha_emision || '—'));
        row.appendChild(td('$' + format(toNumber(item.monto_total || 0))));

        var estadoCell = document.createElement('td');
        var state = (item.estado || 'pendiente').toLowerCase();
        var stateLabel = state.charAt(0).toUpperCase() + state.slice(1);
        estadoCell.innerHTML = '<span class="badge badge--' + state + '">' + stateLabel + '</span>';
        row.appendChild(estadoCell);

        var comprobanteCell = document.createElement('td');
        if (item.comprobante_path) {
          var link = document.createElement('a');
          link.href = '/storage/' + item.comprobante_path;
          link.target = '_blank';
          link.rel = 'noopener';
          link.textContent = 'Ver';
          comprobanteCell.appendChild(link);
        } else {
          comprobanteCell.textContent = '—';
        }
        row.appendChild(comprobanteCell);

        var actions = document.createElement('td');
        var deleteBtn = document.createElement('button');
        deleteBtn.type = 'button';
        deleteBtn.className = 'btn btn-sm btn-danger';
        deleteBtn.textContent = 'Eliminar';
        deleteBtn.dataset.historialDelete = String(item.id || '');
        actions.appendChild(deleteBtn);
        row.appendChild(actions);

        listBody.appendChild(row);
      });
    }

    function fetchHistorial(contratoId) {
      return fetch('/contabilidad/historial?contrato=' + encodeURIComponent(contratoId), {
        headers: { 'Accept': 'application/json' }
      }).then(function (response) {
        if (!response.ok) {
          throw new Error('Error al obtener historial');
        }
        return response.json();
      }).then(function (payload) {
        if (payload && payload.ok && Array.isArray(payload.items)) {
          renderRows(payload.items);
        } else {
          renderRows([]);
        }
      }).catch(function () {
        renderRows([]);
        showToast('No se pudo cargar el historial.', 'error');
      });
    }

    document.querySelectorAll('[data-action="mostrar-historial"]').forEach(function (button) {
      button.addEventListener('click', function () {
        var contratoId = button.getAttribute('data-contrato-id');
        var cooperativaId = button.getAttribute('data-cooperativa-id');
        var cooperativa = button.getAttribute('data-cooperativa') || '';
        var servicio = button.getAttribute('data-servicio') || '';

        currentContratoId = contratoId || '';
        currentCooperativaId = cooperativaId || '';

        if (contratoLabel) {
          contratoLabel.textContent = cooperativa + (servicio ? ' · ' + servicio : '');
        }
        if (form) {
          form.reset();
          if (cooperativaHidden) {
            cooperativaHidden.value = currentCooperativaId;
          }
          if (contratoHidden) {
            contratoHidden.value = currentContratoId;
          }
          if (ivaRateInput) {
            ivaRateInput.value = String(IVA_PERCENT_DEFAULT);
          }
          refreshTotals(true);
        }

        overlay.style.display = 'flex';
        overlay.removeAttribute('hidden');
        fetchHistorial(contratoId);
      });
    });

    if (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var contratoId = currentContratoId;
        if (!contratoId) {
          showToast('Contrato inválido.', 'error');
          return;
        }
        var formData = new FormData(form);
        fetch('/contabilidad/historial', {
          method: 'POST',
          body: formData,
        }).then(function (response) {
          return response.json().then(function (payload) {
            if (!response.ok || !payload || !payload.ok) {
              throw payload;
            }
            return payload;
          });
        }).then(function () {
          showToast('Pago registrado.', 'success');
          form.reset();
          if (cooperativaHidden) {
            cooperativaHidden.value = currentCooperativaId;
          }
          if (contratoHidden) {
            contratoHidden.value = currentContratoId;
          }
          refreshTotals(true);
          fetchHistorial(currentContratoId);
        }).catch(function (error) {
          var message = 'No se pudo guardar el pago.';
          if (error && error.errors) {
            var parts = [];
            Object.keys(error.errors).forEach(function (key) {
              var value = error.errors[key];
              if (Array.isArray(value)) {
                parts = parts.concat(value);
              } else if (value) {
                parts.push(String(value));
              }
            });
            if (parts.length) {
              message = parts.join(' ');
            }
          }
          showToast(message, 'error');
        });
      });
    }

    if (listBody) {
      listBody.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
          return;
        }
        var deleteId = target.dataset ? target.dataset.historialDelete : null;
        if (!deleteId) {
          return;
        }
        if (!window.confirm('¿Eliminar este registro de pago?')) {
          return;
        }
        fetch('/contabilidad/historial/' + encodeURIComponent(deleteId) + '/eliminar', {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        }).then(function (response) {
          if (!response.ok) {
            throw new Error();
          }
          return response.json();
        }).then(function (payload) {
          if (!payload || !payload.ok) {
            throw new Error();
          }
          showToast('Registro eliminado.', 'success');
          fetchHistorial(currentContratoId);
        }).catch(function () {
          showToast('No se pudo eliminar el registro.', 'error');
        });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    setupContractForms();
    setupHistorialModal();
  });
})();
