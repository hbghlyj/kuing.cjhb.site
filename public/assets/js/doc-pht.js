
/*!
 * This file is part of the DocPHT project.
 *
 * @author Valentino Pesce
 * @copyright (c) Valentino Pesce <valentino@iltuobrand.it>
 * @copyright (c) Craig Crosby <creecros@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/* jshint esversion: 6 */

function getCookie(cname) {
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

function setCookie(cname, cvalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays*24*60*60*1000));
  var expires = "expires="+ d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function updateEditLink() {
    var link = document.getElementById('sk-update');
    if (!link) { return; }
    var hash = window.location.hash.substring(1);
    if (hash) {
        link.href = '/page/update?section=' + hash;
    } else {
        link.href = '/page/update';
    }
}


$(document).ready(function () {
    $("#sidebar").mCustomScrollbar({
        theme: "minimal"
    });

    updateEditLink();
    $(window).on('hashchange', updateEditLink);

    var sidebar = getCookie('sidebar');
    if (sidebar === 'in') {
        $('#sidebar, #content').toggleClass('active');
        $('.collapse.in').toggleClass('in');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    }

    $('#sidebarCollapse').on('click', function () {
        var sidebar = getCookie('sidebar');
        if (sidebar === 'in') { setCookie('sidebar', 'out', 1); } else { setCookie('sidebar', 'in', 1); }
        $('#sidebar, #content').toggleClass('active');
        $('.collapse.in').toggleClass('in');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    });

    $('[data-toggle="tooltip"]').tooltip();

    var offset = 300,
    offset_opacity = 1200,
    scroll_top_duration = 700,
    $back_to_top = $('.top');

    $(window).scroll(function(){
        if ($(this).scrollTop() > offset) {
            $back_to_top.addClass('cd-is-visible');
        } else {
            $back_to_top.removeClass('cd-is-visible cd-fade-out');
        }
        if ($(this).scrollTop() > offset_opacity) {
            $back_to_top.addClass('cd-fade-out');
        }
    });

    $back_to_top.on('click', function(event){
        event.preventDefault();
            $('body,html').animate({
                scrollTop: 0 ,
                }, scroll_top_duration
            );
    });

    $('a[href="#search"]').on('click', function(event) {
        event.preventDefault();
        $('#search').addClass('open');
        $('#search > form > input[type="search"]').focus();
    });

    $('#search, #search button.close').on('click keyup', function(event) {
        if (event.target == this || event.target.className == 'close' || event.keyCode == 27) {
            $(this).removeClass('open');
        }
    });

    window.onscroll = function() {
        pageScrollIndicator();
    };

    function pageScrollIndicator() {
        var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
        var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        var scrolled = (winScroll / height) * 100;
        document.getElementById("scrollindicator").style.width = scrolled + "%";
    }

    $("#last-logins-search").on("keyup", function() {
        const value = $(this).val().toLowerCase();
        $("#last-logins-table tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $("#change-log-search").on("keyup", function() {
        const value = $(this).val().toLowerCase();
        $("#change-log-table tr").filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

});

// Forms dependent hide and show
var all_options = document.querySelectorAll("[id^='frm-options']");
var all_files = document.querySelectorAll("[id^='frm-file']");
var all_option_content = document.querySelectorAll("[id^='frm-option_content']");

var labels = document.getElementsByTagName('LABEL');
for (var i = 0; i < labels.length; i++) {
    if (labels[i].htmlFor !== '') {
         var elem = document.getElementById(labels[i].htmlFor);
         if (elem)
            elem.label = labels[i];
    }
}


function updateOptionFields() {
    for (var i = 0; i < all_options.length; i++) {
        if (all_options[i].value === "image") {
            all_option_content[i].label.innerHTML = 'Image Name';
            all_files[i].parentNode.parentNode.style.display = "block";
            all_option_content[i].parentNode.parentNode.style.display = "block";
        } else {
            switch (all_options[i].value) {
                case "title":
                    all_option_content[i].label.innerHTML = 'Title:';
                    break;
                default:
                    all_option_content[i].label.innerHTML = 'Content:';
            }
            all_files[i].parentNode.parentNode.style.display = "none";
            all_option_content[i].parentNode.parentNode.style.display = "block";
        }
    }
}

updateOptionFields();

document.addEventListener("change", updateOptionFields);

if (document.getElementById('rvselect')) {

    document.getElementById('ivhidden').value = document.getElementById('rvselect').value;
    document.getElementById('evhidden').value = document.getElementById('rvselect').value;
    document.getElementById('dvhidden').value = document.getElementById('rvselect').value;

    document.getElementById('rvselect').onchange = function() {
        document.getElementById('ivhidden').value = document.getElementById('rvselect').value;
        document.getElementById('evhidden').value = document.getElementById('rvselect').value;
        document.getElementById('dvhidden').value = document.getElementById('rvselect').value;
    };

}

if (document.getElementById('rbbackup')) {

    document.getElementById('rbbackup').onchange = function() {
        document.getElementById('ibhidden').value = document.getElementById('rbbackup').value;
        document.getElementById('ebhidden').value = document.getElementById('rbbackup').value;
        document.getElementById('dbhidden').value = document.getElementById('rbbackup').value;
        document.getElementById('rmbhidden').value = document.getElementById('rbbackup').value;
    };

}
