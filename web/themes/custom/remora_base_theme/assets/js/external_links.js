(function($, Drupal) {
  'use strict';

  Drupal.behaviors.rbt_external_links = {
    attach: function(context, settings) {
      if (context !== document) {
        return;
      }

      // match any url that does not start with / or #, or doesn't contain the current host
      // that last check might cause problems with the share module if the share text contains the current url, but we'll cross that bridge when we get there
      $(`a:not([href^="/"], [href^="#"], [href*="${window.location.host}"])`)
        .attr('target', '_blank')
        .attr('rel', 'noopener')
    }
  };

})(jQuery, Drupal);
