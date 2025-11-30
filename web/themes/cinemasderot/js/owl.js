(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.my_owl_behavior = {
    attach: function (context, settings) {

$(document).ready(function() {


// view carusel

$(".view.movie-slide").each(function(){ 
  if ($(this).children(".view-content").children(".views-row").length != '1') {
    $(this).children(".view-content").owlCarousel({
      rtl: true,
      autoplay:false,
      loop:false,
      nav: true,
      dots: false,
      autoHeight: true,
      navRewind: false,
      responsive: {
        0: {
          items: 1,
          margin:26,
          stagePadding:80,
        },
        769: {
          items: 3,
          margin:26,
          stagePadding:80,
        },
        1025:{
          items: 3,
          margin:36,
          stagePadding:70,
        },
        1201: {
          items: 4,
          margin:46,
          stagePadding:70,
        },
        1351: {
          items: 4,
          margin:46,
          stagePadding:170,
        }
      }
    });
  }
});

if ($(".paragraph--type--movies-gallery").length>0){
  if ($(".field--name-field-galeries-items > .paragraph").length != '1') {
    $(".field--name-field-galeries-items").owlCarousel({
      rtl: true,
      autoplay:true,
      autoplayTimeout:5000,
      autoplayHoverPause:false,
      loop:true,
      nav: false,
      dots: true,
      autoHeight: true,
      items: 1,
      lazyLoad: true,
      margin: 10,
      animateIn: 'fadeIn',
      animateOut: 'fadeOut'
    });
    $(".paragraph--type--movies-gallery .field--name-field-galeries-items img").attr("loading","");
    $(".owl-controls").css("top","calc(50% - " + $(".owl-controls").height()/2 + "px");
  }
}

//calendar
$(".calendar-inner .view-content").owlCarousel({
  rtl: true,
  autoplay:false,
  autoplayTimeout:5000,
  autoplayHoverPause:false,
  loop:false,
  nav: true,
  dots: false,
  margin: 10,
  autoHeight: true,
  items: 1,
  navRewind: false,
  animateIn: 'fadeIn', // add this
  animateOut: 'fadeOut' // and this
});



});
    	

     }
  };

})(jQuery, Drupal);