if (window.top !== window.self && window.location.pathname != '/donation-thank-you') {
  document.querySelector('html').style.display = 'none';
  window.top.location.href = window.self.location.href;
}
(function ($, Drupal, drupalSettings) {

  'use strict';
  Drupal.behaviors.meshulam = {
    attach: function (context) {
    }
  };

})(jQuery, Drupal, drupalSettings);
