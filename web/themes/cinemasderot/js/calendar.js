(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.calendar_behavior = {
    attach: function (context, settings) {
        $(document).ready(function() {

  /*    
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

$("input.js-form-submit").click(function() {
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
          var $td = $('td[data-date="' + dateParam + '"]');
          if ($td.length) {
            if (!$td.hasClass('active')) {
              $td.click();
              $td.addClass('active');
            }
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
    var newdate = $(".fc-day-top.active").attr("data-date");
    var date = new Date(newdate);
    var year = date.getFullYear();
    var month = (1 + date.getMonth()).toString();
    month = month.length > 1 ? month : '0' + month;
    var day = date.getDate().toString();
    day = day.length > 1 ? day : '0' + day;
    var newdate = day + '/' + month + '/' + year;
    var text = " אירועים ביום " + newdate;
    $(".text-empty h3").replaceWith(text);
    $("#block-cinemasderot-content .view-grouping-header").html(text);
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
*/

      if ($(".path-calendar").length > 0) {
        $(".view-full-calendar td[colspan='2']").removeAttr("colspan");
        $(".fc-other-month").each(function(){
          var Index = $(this).index() + 1;
          $(this).closest("table").find("tbody td:nth-child("+Index+")").addClass("hide-opacity");
        });
        $(".fc-day-number").off('click').on('click', function (e) {
          var date = $(this).parent().attr("data-date");
          $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-min input").val(date);
          var nextDay = new Date(date);
          nextDay.setDate(nextDay.getDate() + 1);
          var yyyy = nextDay.getFullYear();
          var mm = String(nextDay.getMonth() + 1).padStart(2, '0');
          var dd = String(nextDay.getDate()).padStart(2, '0');
          var nextDayStr = yyyy + '-' + mm + '-' + dd;
          $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-max input").val(nextDayStr);
          var parentIndex = $(this).parent().index() + 1;
          var fcWidgetContent = $(this).closest(".fc-widget-content").index() + 1;
          if (!$(this).parent().hasClass("active")){
            $(this).closest("table").find("tbody tr:first-child td:nth-child("+parentIndex+")").addClass("active");
            $(".fc-day-top[data-date="+date+"]").addClass("active");
            $(".view-full-calendar .view-footer .views-exposed-form .form-submit").trigger("click");
            $(document).one('ajaxComplete', function() {
              $(".fc-day-top[data-date="+date+"]").addClass("active");
              $(".fc-day-top[data-date="+date+"]").closest("table").find("tbody tr:first-child td:nth-child("+parentIndex+")").addClass("active");
            });
            
          }else{
              $(this).closest("table").find("tbody tr:first-child td:nth-child("+parentIndex+")").addClass("active");
              $(".fc-day-top[data-date="+date+"]").addClass("active");
              $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-min input").val("first day of this month");
              $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-max input").val("last day of this month");
              $(".view-full-calendar .view-footer .views-exposed-form .form-submit").trigger("click");
          }
          if ($(window).width() < 1025) {
            $('html, body').animate({
              scrollTop: $('.view-full-calendar').offset().top - 100
            }, 800);
          }
        });
        $(".fc-next-button").off('click').on('click', function (e) {
          var currentDate = $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-min input").val();
          if (currentDate == "first day of this month"){
            var date = new Date();
            date.setDate(1);
          } else {
            var date = new Date(currentDate);
          }
          date.setMonth(date.getMonth() + 1);
          date.setDate(1);
          var yyyy = date.getFullYear();
          var mm = String(date.getMonth() + 1).padStart(2, '0');
          var dd = String(date.getDate()).padStart(2, '0');
          var firstDayNextMonth = yyyy + '-' + mm + '-' + dd;
          $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-min input").val(firstDayNextMonth);
          $(".view-full-calendar .right-area .views-exposed-form .form-item-field-date-value-min input").val(firstDayNextMonth);
          var nextNextMonth = new Date(date);
          nextNextMonth.setMonth(nextNextMonth.getMonth() + 1);
          nextNextMonth.setDate(1);
          var yyyy2 = nextNextMonth.getFullYear();
          var mm2 = String(nextNextMonth.getMonth() + 1).padStart(2, '0');
          var dd2 = String(nextNextMonth.getDate()).padStart(2, '0');
          var firstDayNextNextMonth = yyyy2 + '-' + mm2 + '-' + dd2;
          $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-max input").val(firstDayNextNextMonth);
          $(".view-full-calendar .right-area .views-exposed-form .form-item-field-date-value-max input").val(firstDayNextNextMonth);

          setTimeout(function() {
            $(".view-full-calendar .right-area .views-exposed-form .form-submit").click();
          }, 100);
        });
        $(".fc-prev-button").off('click').on('click', function (e) {
          var currentDate = $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-min input").val();
          if (currentDate == "first day of this month"){
            var date = new Date();
            date.setDate(1);
          } else {
            var date = new Date(currentDate);
          }
          date.setMonth(date.getMonth() - 1);
          date.setDate(1);
          var yyyy = date.getFullYear();
          var mm = String(date.getMonth() + 1).padStart(2, '0');
          var dd = String(date.getDate()).padStart(2, '0');
          var firstDayPrevMonth = yyyy + '-' + mm + '-' + dd;
          $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-min input").val(firstDayPrevMonth);
          $(".view-full-calendar .right-area .views-exposed-form .form-item-field-date-value-min input").val(firstDayPrevMonth);
            var nextMonth = new Date(date);
            nextMonth.setMonth(nextMonth.getMonth() + 1);
            nextMonth.setDate(1);
            var yyyyNext = nextMonth.getFullYear();
            var mmNext = String(nextMonth.getMonth() + 1).padStart(2, '0');
            var ddNext = String(nextMonth.getDate()).padStart(2, '0');
            var firstDayNextMonth = yyyyNext + '-' + mmNext + '-' + ddNext;
            $(".view-full-calendar .view-footer .views-exposed-form .form-item-field-date-value-max input").val(firstDayNextMonth);
            $(".view-full-calendar .right-area .views-exposed-form .form-item-field-date-value-max input").val(firstDayNextMonth);

          setTimeout(function() {
            $(".view-full-calendar .right-area .views-exposed-form .form-submit").click();
          }, 100);
          });

                        
      }




        });

     }
  };

})(jQuery, Drupal);