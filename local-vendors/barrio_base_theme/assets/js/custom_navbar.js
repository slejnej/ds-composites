(function($, Drupal, once) {
  'use strict';

  Drupal.behaviors.custom_navbar = {
    attach: function(context, settings) {
      // Use once() to ensure this runs only once
      once('customNavbar', 'html', context).forEach(function() {
        // Function to initialize when Bootstrap is available
        const initCustomNavbar = function() {
          const bs = window.bootstrap;
          if (!bs || !bs.Dropdown) {
            console.log('Bootstrap not available yet');
            return false;
          }

          console.log('Bootstrap detected, initializing custom navbar');
          const className = 'has-child-dropdown-show';

          // Store original toggle method
          const originalToggle = bs.Dropdown.prototype.toggle;

          // Override the toggle method
          bs.Dropdown.prototype.toggle = function() {
            $('.' + className).removeClass(className);

            let dd = $(this._element).closest('.dropdown').parent().closest('.dropdown');
            while (dd.length && !dd.is(document)) {
              dd.addClass(className);
              dd = dd.parent().closest('.dropdown');
            }

            return originalToggle.call(this);
          };

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

          // Initial check
          setTimeout(checkDropdownPosition, 100);

          return true;
        };

        // Try immediately
        if (!initCustomNavbar()) {
          // If Bootstrap isn't available, set up a polling mechanism
          let retryCount = 0;
          const maxRetries = 30; // Try for 3 seconds max

          const checkInterval = setInterval(function() {
            retryCount++;
            if (initCustomNavbar()) {
              clearInterval(checkInterval);
              console.log('Custom navbar initialized after ' + (retryCount * 100) + 'ms');
            } else if (retryCount >= maxRetries) {
              clearInterval(checkInterval);
              console.error('Bootstrap not loaded after 3 seconds, custom navbar not initialized');
            }
          }, 100);

          // Also try on window load
          $(window).on('load', function() {
            initCustomNavbar();
          });
        }
      });
    }
  };
})(jQuery, Drupal, once);