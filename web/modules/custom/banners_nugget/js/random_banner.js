(function ($, Drupal) {
  'use strict';
  Drupal.behaviors.random_banners = {
    attach: function (context, settings) {
      // Count the number of banners added to nugget
      let getBannerCount = $("#banners").children().length;

      // Random number between 1 - number of banners
      function getBannerRange (max) {
        return Math.floor(Math.random() * max) + 1;
      }

      let randomBanner = getBannerRange(getBannerCount);

      // Remove d-none from one random banner whoop
      let banner = $(`.banner:nth-child(${randomBanner})`);
      banner.removeClass('d-none');

      }
    }
  }
)(jQuery, Drupal);
