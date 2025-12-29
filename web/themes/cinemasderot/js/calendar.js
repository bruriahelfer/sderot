(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.calendar_behavior = {
    attach: function (context, settings) {
        $(document).ready(function() {

      
$(".view-full-calendar td.fc-day-top").unbind('click').bind('click', function (e) {
  $("#views-exposed-form-calendar-block-2 [id^=edit-field-date-value-min]").val($(this).attr("data-date"));
  var dataDate = $(this).attr("data-date");
  var today = new Date($(this).attr("data-date"));
  var tomorrow = new Date(today);
  tomorrow.setDate(today.getDate()+1);
  var day = `${tomorrow.getDate()}`.padStart(2, '0');
  var month = `${tomorrow.getMonth()+1}`.padStart(2, '0');
  var year = tomorrow.getFullYear();
  var tomorrow_formatted = year+"-"+month+"-"+day;  // FIXED: declared variable and fixed typo
  $("#views-exposed-form-calendar-block-2 [id^=edit-field-date-value-max]").val(tomorrow_formatted);
  $("#views-exposed-form-calendar-block-2 input.js-form-submit").click();
  $("td.active").removeClass("active");
  $("td.fc-day-top[data-date=" + dataDate + "]").addClass("active");
});
if ($(window).width()<1025){
$("#views-exposed-form-calendar-block-2 input.js-form-submit").click(function() {
  $([document.documentElement, document.body]).animate({
      scrollTop: ($(".view-footer").offset().top - 60)
  }, 500); // Much faster - only 0.5 seconds
});
}

$("#views-exposed-form-calendar-block-23 input.js-form-submit").click(function() {
  var newdate = $("td.today").attr("value");
  alert (newdate);
  var newdate = "hihi";
  $(".view-calendar h3").replaceWith(newdate);
  
});

if ($("body").hasClass("path-calendar")){
  if (!$("body").hasClass("clicked")){
    if (window.location.search === '') {
      function waitForCalendar() {
        if ($(".fc-today").length > 0) {
          $("body").addClass("clicked");
          $(".fc-today").click();
        } else {
          setTimeout(waitForCalendar, 100);
        }
      }
      if (document.readyState === 'complete') {
        waitForCalendar();
      } else {
        $(window).on('load', waitForCalendar);
      }
    } else {
      var urlParams = new URLSearchParams(window.location.search);
      var dateParam = urlParams.get('date');
      if (dateParam) {
        function waitForDateCell() {
          var $td = $('td[date-date="' + dateParam + '"]');
          if ($td.length) {
            $td.click();
            $td.addClass('active');
          } else {
            setTimeout(waitForDateCell, 100);
          }
        }
        waitForDateCell();
      }
    }
  }
}



$(document).ajaxComplete(function() {
  $(".fc-day-top[data-date="+$(".js-form-item-field-date-value-min input").attr("value")+"]").addClass("active")
  if ($("body").hasClass("path-calendar")){
    var newdate = $(".date-box.today").attr("date-date");
    var date = new Date(newdate);
    var year = date.getFullYear();
    var month = (1 + date.getMonth()).toString();
    month = month.length > 1 ? month : '0' + month;
    var day = date.getDate().toString();
    day = day.length > 1 ? day : '0' + day;
    var newdate = day + '/' + month + '/' + year;
    var dateheader = $(".date-box.today").attr("headers");
    console.log("newdate:"+newdate);
    if (newdate){
      var text = " אירועים ביום "+dateheader+" - "+newdate;
      $(".text-empty h3").replaceWith(text);
    } else {
      $(".text-empty h3").replaceWith("");
    }
    if ($(".js-form-item-field-date-value-min input").attr("value")==""){
      $.each($('td.date-box'), function(i, val) { 
        if ((!$(this).hasClass("no-entry"))  && (!$(this).hasClass("empty"))) {
          $(this).click();
          return false;
        }
    });
  }
  }
}); 


        });

     }
  };

})(jQuery, Drupal);