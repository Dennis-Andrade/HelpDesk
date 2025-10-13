
(function () {
  function handleReset(button) {
    var form = button.closest('form');
    if (!form) {
      return;
    }
    var fechaField = form.querySelector('#seguimiento-fecha');
    var defaultValue = fechaField ? fechaField.getAttribute('data-default') : '';
    form.reset();
    if (fechaField && defaultValue) {
      fechaField.value = defaultValue;
    }
    form.submit();
  }

  document.addEventListener('DOMContentLoaded', function () {
    var resetBtn = document.querySelector('[data-action="seguimiento-reset"]');
    if (resetBtn) {
      resetBtn.addEventListener('click', function (event) {
        event.preventDefault();
        handleReset(resetBtn);
      });
    }
  });
})();
