(function () {
  'use strict';

  var inputs = document.querySelectorAll('[data-typeahead="generic"]');
  if (!inputs.length) {
    return;
  }

  function getValue(source, key) {
    if (!source || typeof source !== 'object' || !key) {
      return '';
    }
    if (Object.prototype.hasOwnProperty.call(source, key)) {
      return source[key] != null ? String(source[key]) : '';
    }
    return '';
  }

  inputs.forEach(function (input) {
    var url = input.getAttribute('data-suggest-url');
    if (!url) {
      return;
    }

    var minLength = parseInt(input.getAttribute('data-suggest-min') || '3', 10);
    if (Number.isNaN(minLength) || minLength < 1) {
      minLength = 3;
    }
    var valueKey = input.getAttribute('data-suggest-value') || 'term';
    var labelKey = input.getAttribute('data-suggest-label') || '';
    var mergeLabel = input.getAttribute('data-suggest-merge') === 'true';

    var listId = input.getAttribute('list');
    var datalist = listId ? document.getElementById(listId) : null;
    if (!datalist) {
      listId = listId || (input.id ? input.id + '-suggestions' : 'suggest-' + Math.random().toString(36).slice(2));
      datalist = document.createElement('datalist');
      datalist.id = listId;
      input.setAttribute('list', listId);
      if (input.parentNode) {
        input.parentNode.appendChild(datalist);
      }
    }
    if (!datalist) {
      return;
    }

    var controller = null;
    var lastQuery = '';

    function clearOptions() {
      datalist.innerHTML = '';
    }

    function populate(items) {
      clearOptions();
      if (!Array.isArray(items)) {
        return;
      }
      items.slice(0, 10).forEach(function (item) {
        if (!item) {
          return;
        }
        var value = getValue(item, valueKey);
        if (!value) {
          return;
        }
        var label = value;
        if (labelKey) {
          var alt = getValue(item, labelKey);
          if (alt) {
            label = mergeLabel && alt !== value ? value + ' — ' + alt : alt;
          }
        }
        var option = document.createElement('option');
        option.value = value;
        option.label = label;
        option.textContent = label;
        datalist.appendChild(option);
      });
    }

    input.addEventListener('input', function () {
      var term = input.value ? input.value.trim() : '';
      if (term.length < minLength) {
        lastQuery = term;
        clearOptions();
        return;
      }
      if (term === lastQuery) {
        return;
      }
      lastQuery = term;

      if (controller) {
        controller.abort();
      }
      controller = typeof AbortController === 'function' ? new AbortController() : null;

      var fetchUrl = url;
      fetchUrl += (fetchUrl.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(term);

      fetch(fetchUrl, {
        headers: { 'Accept': 'application/json' },
        signal: controller ? controller.signal : undefined
      })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('error');
          }
          return response.json();
        })
        .then(function (data) {
          if (!data || (Array.isArray(data.items) === false)) {
            clearOptions();
            return;
          }
          populate(data.items);
        })
        .catch(function (error) {
          if (error && error.name === 'AbortError') {
            return;
          }
          clearOptions();
        });
    });

    input.addEventListener('blur', function () {
      setTimeout(clearOptions, 220);
    });
  });
})();
