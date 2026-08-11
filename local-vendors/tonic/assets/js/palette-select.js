(function($, Drupal) {
  'use strict';

  Drupal.behaviors.tonic_palette_select = {
    attach: function(context) {
      const elements = once('remora-palette-select', 'input[name="field_palette"]', context);

      // when the user changes the palette color
      $(elements).on('change', function() {
        const val = $(this).data('remoraPalette');

        if(val) {
          $('.layout-select svg rect').css('fill', val);
        }
      });

      // one off on load
      const selectedColor = $('input[name="field_palette"]:checked').data('remoraPalette');

      if(selectedColor) {
        $('.layout-select svg rect').css('fill', selectedColor);
      }
    }
  };

})(jQuery, Drupal);
