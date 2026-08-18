(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.querySelectorAll('textarea');

    textarea.forEach(elm => {
      function autoResize() {
        elm.style.height = 'auto';
        elm.style.height = (elm.scrollHeight + 5) + 'px';
      }

      elm.addEventListener('input', autoResize);
      autoResize();
    });
  });

})();
