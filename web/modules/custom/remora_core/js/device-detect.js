((Drupal) => {

  /**
   * Function to detect if the device is a mobile or tablet.
   * This will be available globally as `Drupal.isMobileOrTablet()`.
   * @return {Boolean} Returns true if the device is mobile or tablet.
   */
  Drupal.isMobileOrTablet = () => {
    let check = false;
    (function(a) {
      if (/android|bb\d+|meego.+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|iphone|ipod|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series([46])0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)) {
        check = true;
      }
    })(navigator.userAgent || navigator.vendor || window.opera);
    return check;
  };

})(Drupal);
