(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.drupal_search_header_block = {
    attach: function (context, settings) {

      $(document).ready(function () {
        $('.header-search-icon').each(function () {
          var $icon = $(this);
          $icon.popover({
            html: true,
            animation: true,
            adaptive: true,
            sanitize: false,
            content: function () {
              return $icon.siblings('.header-search-form').html();
            },
            placement: 'bottom',
            offset: [0, 0],
            trigger: 'manual',
            template: '<div class="popover header-search-popover w-100 m-0 border-0 rounded-0 bg-white" role="tooltip"><div class="popover-body mx-auto"></div></div>'
          }).on('click', function () {
            var button = $(this);
            button.popover('toggle');
          });
        });

        // Popover is shown
        $(document).on('shown.bs.popover', '.header-search-icon', function () {
          $(this).addClass('popover-active');
          $('.popover .search-input').focus();
        });

        // Popover is hidden
        $(document).on('hidden.bs.popover', '.header-search-icon', function () {
          $(this).removeClass('popover-active');
        });

        // Hide the popover when clicking outside
        $(document).on('click', function (e) {
          var $trigger = $('.header-search-icon');
          if (!$trigger.is(e.target) && $trigger.has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
            $trigger.popover('hide').removeClass('popover-active');
          }
        });

        // Prevents the popover from being hidden when clicking inside it
        $(document).on('click', '.popover', function (e) {
          e.stopPropagation();
        });
      });

    }
  };
})(jQuery, Drupal);
