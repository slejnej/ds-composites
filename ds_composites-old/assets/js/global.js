(function($, Drupal) {
  'use strict';

  Drupal.behaviors.gaelf_global = {
    attach: function(context, settings) {
      if (context !== document) {
        return;
      }

      $(window).on('scroll', function () {

        let scrollTopValue = 0;
        let detectedElement = $('.gin-secondary-toolbar');

        if (detectedElement.length && detectedElement.outerHeight(true) > 0) {
          scrollTopValue = detectedElement.outerHeight(true);
        }

        let toolbarAdministrationWidth = 0;
        let toolbarAdministrationElement = $('.toolbar-menu-administration');

        if (toolbarAdministrationElement.length && toolbarAdministrationElement.width() > 0) {
          toolbarAdministrationWidth = toolbarAdministrationElement.width();
        }

        if ($(this).scrollTop() > 0) {
          $('header').css('top', scrollTopValue + 'px');
          $('header').css('left', toolbarAdministrationWidth + 'px');
          $('header').addClass('scrolled');
        } else {
          $('header').removeClass('scrolled');
          $('header').css('top', '0');
          $('header').css('left', '0');
        }
      });
    }
  };

})(jQuery, Drupal);