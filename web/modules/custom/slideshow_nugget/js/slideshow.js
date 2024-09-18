/**
 * @file
 * Slick slider functionality
 *
 */
(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.slideshow_slick = {
    attach: function (context) {
      if (context !== document) {
        return;
      }

      // tabs fix for slick width 0
      $(document).on('shown.bs.tab', function (event) {
        // if there is carousel in the tab and width is 0 then reInit
        if ($(this).find('.slick-track').width() === 0) {
          // it is in a tab so un-init it !
          $(this).find('.slick-slideshow .slick-initialized').slick('refresh')
        }
      })

      // SLICK
      $('.slick-slideshow').not('.slick-initialized').each(function () {
        const elem = $(this);

        elem.on('beforeChange', (e) => {
          refireEvent(e, elem);

          //this is preferred over afterChange - since afterChange is lagging behind
          setTimeout(function() {
            updateDots(e, elem);
          }, 100);
        });

        elem.on('afterChange', (e) => {
          refireEvent(e, elem);
        });

        elem.on('init', (e) => {
          refireEvent(e, elem);
          updateDots(e, elem);
        });

        elem.slick({
          appendDots: $(this).parent().find(".slideshow-dots"),
          prevArrow: $(this).parent().find(".slideshow-prev"),
          nextArrow: $(this).parent().find(".slideshow-next"),
        });
      });

      /**
       * Only show 2 dots before and after the current slider dot
       *
       * @param e
       * @param {jQuery} elem The carousel element (not the .slick-carousel)
       */
      function updateDots(e, elem) {
        elem = elem.parent();

        const allDots = elem.find('.slick-slideshow-dots li');
        // allDots.index('.slick-active') doesn't work after the first element :/
        const activeIndex = allDots.map(function() { return $(this).hasClass('slick-active') ? $(this).index() : null }).get().filter(x => !!x)[0] || 0;
        const length = allDots.length;

        const showingDots = allDots.slice(clamp(activeIndex - 2, 0, length), clamp(activeIndex + 3, 0, length));

        allDots.removeClass(`show slick-active-index-1 slick-active-index-2`);
        showingDots.addClass('show');
        showingDots.each(function() { $(this).addClass(`slick-active-index-${Math.abs($(this).index() - activeIndex)}`) });

      }

      /**
       * Clamp a value between min and max
       * E.g. clamp(-2, 0, 5) will return 0
       *      clamp(3, 0, 5) will return 3
       *      clamp(10, 0, 5) will return 5
       *
       * @param {number} val The value to clamp
       * @param {number} min The minimum value
       * @param {number} max The maximum value
       * @returns {number}
       */
      function clamp(val, min, max) {
        return Math.max(min, Math.min(val, max));
      }

      /**
       * Refires an event so it's site-wide
       *
       * @param e The slick slider event
       * @param {jQuery} elem The slick slider carousel (not the .slick-carousel)
       */
      function refireEvent(e, elem) {
        $('body').trigger(`slick-slider-${e.type}`, [e, elem]);
      }
    }
  };
})(jQuery, Drupal);
