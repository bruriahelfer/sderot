(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.my_custom_behavior = {
    attach: function (context, settings) {



//// MENU

function autoHeightAnimate(element, time){
  var curHeight = element.height(),
  autoHeight = element.css('height', 'auto').height();
  element.height(curHeight);
  element.stop().animate({ height: autoHeight }, time);
}

$(".menu-item--expanded .mobile-arrow").unbind('click').bind('click', function (e) {
  $(this).parent().toggleClass("open-menu");
  var nav = $(this).parent().find("ul");
  if (nav.height() === 0) {
    autoHeightAnimate(nav, 500);
  }
  else {
    nav.stop().animate({ height: '0' }, 500);
  }
});

$(".menu-item--expanded .field--name-link span").unbind('click').bind('click', function (e) {
  $(this).closest(".menu-item--expanded").toggleClass("open-menu");
  var nav = $(this).closest(".menu-item--expanded").find("ul");
  if (nav.height() === 0) {
    autoHeightAnimate(nav, 500);
  }
  else {
    nav.stop().animate({ height: '0' }, 500);
  }
});


$(document).unbind('click').bind('click', function (e) {
  if(!$(e.target).closest($(".order-form-button")).length) {
    $("header .order-form-button").removeClass("open");
  }
});


$(".calendar .calendar-icon").unbind('click').bind('click', function (e) {
  $(".calendar").toggleClass("open");
  $(".order-form-button").removeClass("open");
});

$(document).unbind('click').bind('click', function (e) {
  if(!$(e.target).closest($(".view-calendar")).length) {
    if(!$(e.target).closest($(".calendar-icon")).length) {
      if(!$(e.target).closest($(".fixed-area-wrapper .close")).length) {
        $(".calendar").removeClass("open");
      }
    }
  }
});



// MOVIE PAGE

$(".withtrailer .field--name-field-image-horizontal").unbind('click').bind('click', function (e) {
  var src=$(this).parent().children(".field--name-field-trailer").find("iframe").attr("src");
  $(this).parent().children(".field--name-field-trailer").find("iframe").attr("allow","autoplay");
  src = src.replace("?autoplay=0", "?autoplay=1&mute=0"); 
  $(this).parent().children(".field--name-field-trailer").find("iframe").attr("src",src);
  e.preventDefault();
  $(this).remove();
});

// SCREEINING TABLE

if ($(".paragraph--type--screening-table").length > 0){

  $(document).ready(function() {
    $(".second-line select").select2({
      minimumResultsForSearch: -1
    });
  });

  $(function() {
    var hebrew_daterangepicker = {
      direction: "rtl",
      format: 'DD.MM.YYYY',
      "daysOfWeek": [
          "א",
          "ב",
          "ג",
          "ד",
          "ה",
          "ו",
          "ש"
      ],
      "monthNames": [
          "ינואר",
          "פברואר",
          "מרץ",
          "אפריל",
          "מאי",
          "יוני",
          "יולי",
          "אוגוסט",
          "ספטמבר",
          "אוקטובר",
          "נובמבר",
          "דצמבר"
      ],
  };
    $('input[name="daterange"]').daterangepicker({
      opens: 'left',
      direction: "rtl",
      minDate: new Date(),
      autoApply: true,
      locale : hebrew_daterangepicker,
      autoUpdateInput: false,
    }, function(start, end, label) {
      end = end.subtract(-1, 'days');
      console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
      $("[id^=edit-field-date-value-1-min]").val(start.format('YYYY-MM-DD'));
      $("[id^=edit-field-date-value-1-max]").val(end.format('YYYY-MM-DD'));
      $('input[name="daterange"]').val(start.format('DD.MM.YYYY') + ' - ' + end.format('DD.MM.YYYY'));
      $(".paragraph--type--screening-table .form-actions input[id^=edit-submit-movies]").click();
    });
  });

  if ($("[id^=edit-field-date-value-1-min]").val()!=""){
    var dateMin = $("[id^=edit-field-date-value-1-min]").val();
    var year = dateMin.substr(0,4);
    var month = dateMin.substr(5,2);
    var day = dateMin.substr(8,2);
    dateMin = day + '.' + month + '.' + year;
    var dateMax = $("[id^=edit-field-date-value-1-max]").val();
    var dateStr = dateMax;
    var result = new Date(new Date(dateStr).setDate(new Date(dateStr).getDate() - 1)).toISOString().substr(0, 10);
    var year = result.substr(0,4);
    var month = result.substr(5,2);
    var day = result.substr(8,2);
    dateMax = day + '.' + month + '.' + year;
    $('input[name="daterange"]').attr("value",dateMin + ' - ' + dateMax);
    $(".date-range .input").addClass("input-full");
  } else {
    $('input[name="daterange"]').val("");
    $(".date-range .input").removeClass("input-full");
  }





  $(document).ajaxComplete(function () {
    if ($("[id^=edit-field-date-value-1-max]").val()!=""){
      var dateMin = $("[id^=edit-field-date-value-1-min]").val();
      var year = dateMin.substr(0,4);
      var month = dateMin.substr(5,2);
      var day = dateMin.substr(8,2);
      dateMin = day + '.' + month + '.' + year;
      var dateMax = $("[id^=edit-field-date-value-1-max]").val();
      var dateStr = dateMax;
      var result = new Date(new Date(dateStr).setDate(new Date(dateStr).getDate() - 1)).toISOString().substr(0, 10);
      var year = result.substr(0,4);
      var month = result.substr(5,2);
      var day = result.substr(8,2);
      dateMax = day + '.' + month + '.' + year;

      $('input[name="daterange"]').attr("value",dateMin + ' - ' + dateMax);
      if ($(".date-range .input input").val()!=""){
        $(".date-range .input").addClass("input-full");
      }
    } else {
      $('input[name="daterange"]').val("");
      $(".date-range .input").removeClass("input-full");
    }
    var empty = true;
    $(".paragraph--type--screening-table .views-exposed-form select").each(function(){  
      if ($(this).children("option[selected='selected']").attr("value")!="All"){
        empty=false;
      }
    });
    if ($(".paragraph--type--screening-table .views-exposed-form .first-line input").val().length != 0){
      empty=false;
    }
    $(".paragraph--type--screening-table .views-exposed-form .form-type-date input").each(function(){  
      if ($(this).val().length != 0){
        empty=false;
      }
    });
    if (empty == true){
      $(".paragraph--type--screening-table .views-exposed-form .form-actions").addClass("no-need-reset");
    } else {
      $(".paragraph--type--screening-table .views-exposed-form .form-actions").removeClass("no-need-reset");
    }
  });

  $(".date-range .input .empty").unbind('click').bind('click', function (e) {
    $("[id^=edit-field-date-value-1-min]").val("");
    $("[id^=edit-field-date-value-1-max]").val("");
    $(".form-item-field-online-value-1 input").prop( "checked", false );
    $(".paragraph--type--screening-table .form-actions input[id^=edit-submit-movies]").click();
  });

  $(".paragraph--type--screening-table .view-filters .filters").unbind('click').bind('click', function (e) {
    $(".paragraph--type--screening-table .mobile-wrapper").addClass("open");
  });
  
  $(".paragraph--type--screening-table .mobile-wrapper .close").unbind('click').bind('click', function (e) {
    $(".paragraph--type--screening-table .mobile-wrapper").removeClass("open");
  });

  $(".paragraph--type--screening-table .view-empty .reset").unbind('click').bind('click', function (e) {
    $(".paragraph--type--screening-table .form-actions input[id^=edit-reset]").click();
  });

}	

//LOADER

Drupal.theme.ajaxProgressIndicatorFullscreen = () => '<div class="ajax-progress"><div class="text">בטעינה, רק רגע</div></div>';


// TODAY WEEK MONTH PG
/*
if ($(".paragraph--type--today-week-month").length > 0){
  $(".paragraph--type--today-week-month .tabs span").unbind('click').bind('click', function (e) {
    if (!$(this).hasClass("active")){
      var activeslide = $(this).attr("class");
      $(".paragraph--type--today-week-month .tabs .active").removeClass("active");
      $(this).addClass("active");
      $(".paragraph--type--today-week-month .sliders .active").removeClass("active");
      $(".paragraph--type--today-week-month .sliders ."+activeslide).addClass("active");
    }
  });
}
*/
// GALLERY COOKIES

if ($(".fixed-area-wrapper").length>0){
  $(".fixed-area-wrapper").addClass("show");
  $(".fixed-area-wrapper .close").unbind('click').bind('click', function (event) {
      $(".fixed-area-wrapper").addClass("hide").removeClass("show");
      setAgreeCookie($(".fixed-area-wrapper .views-row").attr("class").replace(' views-row', '').replace('views-row ', ''));
  });      
  function setAgreeCookie(blockid) {
      var expire=new Date();
      expire=new Date(expire.getTime()+1000*60*60*24);
      document.cookie="blockid="+blockid+"; MSG=yes; expires="+expire+"; path=/";
  }
  var blockid=GetCookie("blockid");
  if (blockid){
    if ($('.fixed-area-wrapper .'+blockid).length > 0){
      $('.fixed-area-wrapper .'+blockid).closest(".fixed-area-wrapper").addClass('hide').removeClass("show");
    }
  }
  function GetCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }
}


// ORDER TICKETS

$(document).ready(function() {
  $("header .order-form-button select").select2({
    minimumResultsForSearch: -1
  });
  if ($(".paragraph--type--order-tickets").length > 0){
    $(".paragraph--type--order-tickets select").select2({
      minimumResultsForSearch: -1
    });
  }
});

$(document).on('mouseenter', '.select2-selection__rendered', function () {
  $('.select2-selection__rendered').removeAttr('title');
});


// FOOTER and HEADER MENU - LINK WITH QUARY

var today = new Date();
var todaymonth = today.getMonth()+1;
var todayday = today.getDate();
var todaydate = today.getFullYear() + '-' + (todaymonth<10 ? '0' : '') + todaymonth + '-' + (todayday<10 ? '0' : '') + todayday;
var tommorow = new Date(today.getTime() + 24 * 60 * 60 * 1000);
var tommorowmonth = tommorow.getMonth()+1;
var tommorowday = tommorow.getDate();
var tommorowdate = tommorow.getFullYear() + '-' + (tommorowmonth<10 ? '0' : '') + tommorowmonth + '-' + (tommorowday<10 ? '0' : '') + tommorowday;
var weekplus = new Date(today.setDate((today.getDate()+7) - (today.getDay())));
var weekplusmonth = weekplus.getMonth()+1;
var weekplusday = weekplus.getDate();
var weekplusdate = weekplus.getFullYear() + '-' + (weekplusmonth<10 ? '0' : '') + weekplusmonth + '-' + (weekplusday<10 ? '0' : '') + weekplusday;
var today = new Date();
var monthplus =new Date(today.getFullYear(), today.getMonth()+1 , 0 );
var monthplus = new Date(monthplus.getTime() + 24 * 60 * 60 * 1000);
var monthplusmonth = monthplus.getMonth()+1;
var monthplusday = monthplus.getDate();
var monthplusdate = monthplus.getFullYear() + '-' + (monthplusmonth<10 ? '0' : '') + monthplusmonth + '-' + (monthplusday<10 ? '0' : '') + monthplusday;
$(".menu-item").each(function(){  
  if ($(this).find("a").hasClass("today")){
    if (!$(this).find(".today").hasClass("withquary")){
      $(this).find(".today").attr("href",$(this).find(".today").attr("href")+"?field_date_value_1[min]="+todaydate+"&field_date_value_1[max]="+tommorowdate+"&field_online_value_1=1").addClass("withquary");
    }
  } else if ($(this).find("a").hasClass("week")){
    if (!$(this).find(".week").hasClass("withquary")){
      $(this).find(".week").attr("href",$(this).find(".week").attr("href")+"?field_date_value_1[min]="+todaydate+"&field_date_value_1[max]="+weekplusdate+"&field_online_value_1=1").addClass("withquary");
    }
  } else if ($(this).find("a").hasClass("month")){
    if (!$(this).find(".month").hasClass("withquary")){
      $(this).find(".month").attr("href",$(this).find(".month").attr("href")+"?field_date_value_1[min]="+todaydate+"&field_date_value_1[max]="+monthplusdate+"&field_online_value_1=1").addClass("withquary");
    }
  }
});


     }
  };

})(jQuery, Drupal);