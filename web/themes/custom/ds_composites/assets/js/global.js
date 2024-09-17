(function($, Drupal) {
  'use strict';

  Drupal.behaviors.gaelf_global = {
    attach: function(context, settings) {
      if (context !== document) {
        return;
      }

    }
  };

})(jQuery, Drupal);