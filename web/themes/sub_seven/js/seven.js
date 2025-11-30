(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.my_custom_seven_behavior = {
    attach: function (context, settings) {


      $(document).ready(function() {
        $("#edit-field-genre-wrapper option[value='_none'").remove();
        $('#edit-field-genre-wrapper select').select2();
    });

    
    if ($(".block-stick-message-form").length > 0){
      if ($(".js-form-item-info-0-value input").val()==""){
        var today=new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
        var yyyy = today.getFullYear();
        today = dd + '.' + mm + '.' + yyyy;
        $(".js-form-item-info-0-value input").val("נכתבה בתאריך" + today);
      }
    }
    	

     }
  };

})(jQuery, Drupal);