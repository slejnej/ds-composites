(function($, Drupal) {
  'use strict';

  Drupal.behaviors.custom_navbar = {
    attach: function(context, settings) {
      if (context !== document) {
        return;
      }

      const bs = window.bootstrap;
      const className = 'has-child-dropdown-show';

      // override default bs dropdown behaviour
      bs.Dropdown.prototype.toggle = (function(_original) {
        return function() {
          $('.' + className).removeClass(className);

          let dd = $(this._element).closest('.dropdown').parent().closest('.dropdown');
          while (dd.length && !dd.is(document)) {
            dd.addClass(className);
            dd = dd.parent().closest('.dropdown');
          }

          return _original.call(this);
        };
      })(bs.Dropdown.prototype.toggle);

      // Overrides default bootstrap dropdown and prevents the dropdowns from hiding for multi-level menus
      $('.navigation.menu--main .dropdown').each(function() {
        $(this).on('hide.bs.dropdown', function(e) {
          if ($(this).hasClass(className)) {
            $(this).removeClass(className);
            e.preventDefault();
          }
          e.stopPropagation();
        });
      });

      // Function to check if a dropdown is off-screen and adjust its positioning
      function checkDropdownPosition() {
        $('.navbar-nav .dropdown').each(function() {
          const dropdownMenu = $(this).find('.dropdown-menu:first');

          if (dropdownMenu.length && dropdownMenu.is(':visible')) {
            dropdownMenu.removeClass('dropdown-menu-end');

            if (dropdownMenu.offset().left + dropdownMenu.outerWidth() > $(window).width()) {
              dropdownMenu.addClass('dropdown-menu-end');
            }
          }
        });

        $('.navbar-nav .dropend').each(function() {
          const dropdownMenu = $(this).find('.dropdown-menu:first');

          if (dropdownMenu.length && dropdownMenu.is(':visible')) {
            $(this).removeClass('dropstart').addClass('dropend');

            if (dropdownMenu.offset().left + dropdownMenu.outerWidth() > $(window).width()) {
              $(this).removeClass('dropend').addClass('dropstart');
            }
          }
        });
      }

      // Run the function on document ready and when the window is resized
      $(window).on('resize show.bs.dropdown', function() {
        setTimeout(checkDropdownPosition, 1);
      });

    }
  };

})(jQuery, Drupal);