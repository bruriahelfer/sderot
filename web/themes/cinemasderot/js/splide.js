(function ($, Drupal, once) {

  'use strict';

  Drupal.behaviors.splide = {
    attach: function (context, settings) {
      $(document).ready(function() {

      // Gallery slider
    once('splide-init-gallery', '.view-content-gallery', context).forEach(function(element) {
      var splide = new Splide(element, {
        direction: 'rtl',
        type: 'loop',
        drag   : 'free',
        focus    : 'center',
        autoWidth: true,
        gap: '14px',
        arrows: false,
        pagination: false,
        autoScroll: {
          speed: 1,
          pauseOnHover: false, 
        },
      });
      
      splide.mount(window.splide.Extensions);
    });



      });
    }
  };

})(jQuery, Drupal, once);