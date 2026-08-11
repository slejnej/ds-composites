(function($, Drupal) {
  'use strict';

  Drupal.behaviors.tonic_palette_preview = {
    attach: function(context) {
      const elements = once('remora-palette-select', '.palette-preview', context);

      console.log(elements);

      elements.forEach(elem => {
        const selectedColor = $(elem).find('[data-remora-palette]').data('remoraPalette');

        console.log(selectedColor);

        $(elem).find('svg rect').css('fill', selectedColor);
      })
    }
  };

})(jQuery, Drupal);
