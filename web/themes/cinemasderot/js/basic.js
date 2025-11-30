(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.my_custom_basic = {
    attach: function (context, settings) {

        // hamburger menu


        $("body").addClass("close-menu");
        $(".hamburger").unbind('click').bind('click', function (e) {
          if ($("body").hasClass("open-menu")){
            $("body").removeClass("open-menu");
            $("body").addClass("close-menu");
          }
          else {
            $("body").addClass("open-menu");
            $("body").removeClass("close-menu");
            $("body").removeClass("open-search");
          }
        });

        $(".layout-center").unbind('click').bind('click', function (e) {
            $("body").removeClass("open-menu");
            $("body").addClass("close-menu");
        });

        $(document).unbind('click').bind('click', function (e) {
          if(!$(e.target).closest($(".region-menu")).length) {
            if(!$(e.target).closest($(".hamburger")).length) {       
              $( "body" ).removeClass( "open-menu" );
              $( "body" ).addClass( "close-menu" );
            }
          }
        });

 

      /********  link active   ***********/

     var path = window.location.href; 
     $('a').each(function() {
      if (this.href === path) {
       $(this).addClass('active');
      }
     });



        /********  scroll ********/

        var scroll_pos = 0;
        $(document).scroll(function(e) {
            scroll_pos = $(this).scrollTop();
            if(scroll_pos > 0) {
              $("body").addClass('scroll');
              if ($("#toolbar-administration").length > 0){
                $("header").css("top",$('body').css('padding-top'));
              }
              $("body").removeClass('not-scroll');
            }
            else {
              $("body").removeClass('scroll');
              if ($("#toolbar-administration").length > 0){
                $("header").css("top","initial");
              }
              $("body").addClass('not-scroll');
            }
        });

        var screenTop = $(document).scrollTop();
        if (screenTop > 0){
              $("body").addClass('scroll');
              if ($("#toolbar-administration").length > 0){
                $("header").css("top",$('body').css('padding-top'));
              }
        } else{
              $("body").addClass('not-scroll');
        }

/******  input & textarea & select********/


      $("input").on("input",function(){
        $(this).parent().addClass("input-full");
      });

      $("input").each(function(){
        if($(this).val() == ''){
          $(this).parent().removeClass("input-full");
        }
        else{
          $(this).parent().addClass("input-full");
        }
      });

      $("input").blur(function(){
        if($(this).val() == ''){
          $(this).parent().removeClass("input-full");
        }
        else{
          $(this).parent().addClass("input-full");
        }
      });

      $("input").focus(function(){
        $(this).parent().addClass("input-full");
      });

      $("textarea").on("input",function(){
      if($(this).val() != ''){
        $(this).parent().parent().addClass("input-full");
      } else {
        $(this).parent().parent().removeClass("input-full");
      }
      });

      $("textarea").each(function(){
        if($(this).val() == ''){
          $(this).parent().parent().removeClass("input-full");
        }
        else{
          $(this).parent().parent().addClass("input-full");
        }
      });

      $("textarea").blur(function(){
        if($(this).val() == ''){
          $(this).parent().parent().removeClass("input-full");
        }
        else{
          $(this).parent().parent().addClass("input-full");
        }
      });

      $("select").each(function(){
        if($(this).val() == ''){
          $(this).parent().removeClass("input-full");
        }
        else{
          $(this).parent().addClass("input-full");
        }
      });

      $('select').change(function(){
        if($(this).val() == ''){
          $(this).parent().removeClass("input-full");
        }
        else{
          $(this).parent().addClass("input-full");
        }
      });


     }
  };

})(jQuery, Drupal);




document.addEventListener("DOMContentLoaded", function() {
    var elements = document.getElementsByTagName("INPUT");
    for (var i = 0; i < elements.length; i++) {
        elements[i].oninvalid = function(e) {
            e.target.setCustomValidity("");
            if (!e.target.validity.valid) {
                var message = Drupal.t('Please fill out this field');
                e.target.setCustomValidity(message);
                $(this).parent().addClass("ERROR");
            }
        if (e.target.validity.typeMismatch) {
          var message = Drupal.t('Please fill out a valid email');
          $(this).parent().addClass("ERROR");
        e.target.setCustomValidity(message);
        }
        };
        elements[i].oninput = function(e) {
            e.target.setCustomValidity("");
        };
    }

})