(function($, Drupal) {
  'use strict';

  Drupal.behaviors.ds_composites_global = {
    attach: function(context, settings) {
      if (context !== document) {
        return;
      }

    }
  };

})(jQuery, Drupal);