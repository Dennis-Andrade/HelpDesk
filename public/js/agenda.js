(function () {
  'use strict';
  function autoDismissToasts() {
    const toasts = document.querySelectorAll('.agenda-page__toast');
    toasts.forEach(function (toast) {
      setTimeout(function () {
        toast.classList.add('agenda-toast-hide');
        setTimeout(function () { toast.remove(); }, 400);
      }, 10000);
    });
  }
  document.addEventListener('DOMContentLoaded', autoDismissToasts);
})();
