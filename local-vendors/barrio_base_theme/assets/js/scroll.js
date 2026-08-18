(function($, Drupal) {
  'use strict';

  /**
   * Shows shadows on tabs that extend past their container's width
   *
   * @type {{attach: Drupal.behaviors.rbt_scroll.attach}}
   */
  Drupal.behaviors.rbt_scroll = {
    attach: function (context, settings) {
      if (context !== document) {
        return;
      }

      /** A jQuery object for this.element */
      /** The percentage the user needs to scroll to show the shadows. Defaults to 10 **/
      const displayThreshold = 10 / 100;

      function onWindowResize () {
        $('ul.nav-tabs,ul.nav-pills').each(onScroll);
      }

      $('ul.nav-tabs, ul.nav-pills').on('scroll', onScroll);


      let resizeTimeout = null;

      // Run the function on document ready and when the window is resized
      $(window).on('load resize', function() {
        if (resizeTimeout) {
          return;
        }

        resizeTimeout = requestAnimationFrame(() => {
          resizeTimeout = null;
          onWindowResize();
        });
      });

      function onScroll (){
        const elem = $(this);
        const realWidth = elem.get(0).scrollWidth;
        const visibleWidth = elem.width();
        const scrollableWidth = realWidth - visibleWidth;
        const scrolledDistance = elem.scrollLeft();

        elem
          .css('--distance-scrolled', `${scrolledDistance}px`)
          .toggleClass('start-visible', scrolledDistance / scrollableWidth >= displayThreshold)
          .toggleClass('end-visible', scrolledDistance / scrollableWidth <= (1 - displayThreshold));
      }

    }
  }

})(jQuery, Drupal);
