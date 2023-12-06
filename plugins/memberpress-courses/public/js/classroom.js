(function ($) {

  $(document).ready(function () {
    if ($('.mpcs-share').length) {
      copyShareableCertLink = new ClipboardJS('.mpcs-share');
      $('.mpcs-share').tooltipster({
        trigger: 'click'
      });
    }
    $(".course-progress").each(function (i, e) {
      var progress_bar = $(".user-progress", e);
      var progress = 0;
      var interval = setInterval(expand_progress, 10);
      var target_progress = progress_bar.data("value");
      progress_bar.html("&nbsp;");

      function expand_progress() {
        if (progress >= target_progress) {
          clearInterval(interval);
        } else {
          progress++;
          progress_bar.width(progress + "%");
        }
      }
    });


    $(".mpcs-progress-ring").each(function (i, e) {
      setProgress($(this), $(this).data("value"));

      function setProgress($el, end, i) {
        let color = $el.data("color");
        if (end < 0)
          end = 0;
        else if (end > 100)
          end = 100;
        if (typeof i === 'undefined')
          i = 0;
        var curr = (100 * i) / 360;
        $el.find(".stat").html(Math.round(curr));
        if (i <= 180) {
          $el.css('background-image', 'linear-gradient(' + (90 + i) + 'deg, transparent 50%, #ccc 50%),linear-gradient(90deg, #ccc 50%, transparent 50%)');
        } else {
          $el.css('background-image', 'linear-gradient(' + (i - 90) + 'deg, transparent 50%, rgba('+color+') 50%),linear-gradient(90deg, #ccc 50%, transparent 50%)');
        }
        if (curr < end) {
          setTimeout(function () {
            setProgress($el, end, ++i);
          }, 1);
        }
      }
    });


    $("#mpcs-sidebar-toggle").click(function () {
      $("#mpcs-sidebar").toggleClass("is-active");
    });

    // Dropdown Toggle
    $(".dropdown-toggle").on("click", function (event) {
      event.preventDefault();
      let $closest = $(this).closest(".dropdown");
      $(".dropdown").not($closest).removeClass("active");
      $closest.toggleClass("active");
    });

    $(document).on("click", function (event) {
      let $target = $(event.target);
      if (!$target.closest(".dropdown").length) {
        $(".dropdown").removeClass("active");
      }
    });

    $(".btn.sidebar-open").on("click", function (event) {
      $("#mpcs-sidebar, #mpcs-main").toggleClass('off-canvas');
    });

    $(".btn.sidebar-close").on("click", function (event) {
      $("#mpcs-sidebar, #mpcs-main").removeClass('off-canvas');
    });

    $('.mpcs-course-filter .dropdown').each(function(){
      let $active = $(this).find('li.active');
      if($active.length > 0){
        $(this).find('.dropdown-toggle span').html($active.text());
      }
    });


    $('.mpcs-dropdown-search').change(dropdownFilter).keyup(dropdownFilter);

    function dropdownFilter() {
      let input, filter, li, a, i;
      input = this;
      filter = input.value.toUpperCase();
      li = $( input ).closest('li').siblings();

      for (i = 0; i < li.length; i++) {
        txtValue = li[i].textContent || li[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          li[i].style.display = "";
        } else {
          li[i].style.display = "none";
        }
      }
    }

    // Course Accordion
    if ($('body').hasClass('mpcs-course-with-accordion')) {
      var headers = $('#mpcs-main .mpcs-section-header');
      var current = $('#mpcs-main .mpcs-lesson.current');
      if (current.length) {
        var header = current.closest('.mpcs-section').find('.mpcs-section-header');
        header.addClass('active');
        header.next('.mpcs-lessons').css('display', 'block');
      }
      $(headers).on('click', function () {
        var $this = $(this);
        $this.toggleClass('active');
        if ($this.hasClass('active')) {
          $this.next('.mpcs-lessons').css('display', 'block');
        } else {
          $this.next('.mpcs-lessons').css('display', 'none');
        }
      });
    }

    // Auto adjust Sidebar Content Height
    $('.mpcs-sidebar-content').css('height', function( index ) {
      var height = $('#mpcs-navbar').outerHeight() + $('#mpcs-sidebar-header').outerHeight();
      return "calc(100% - " + height +"px)";
    });

    // Auto adjust sidebar content height on Course page
    if ($('.single-mpcs-course .mpcs-sidebar-content').length) {
      var offset = $('#mpcs-navbar').outerHeight();
      var courseImage = $('.single-mpcs-course #mpcs-sidebar .figure img');
      var courseProgress = $('.single-mpcs-course #mpcs-sidebar .course-progress');
      if (courseImage.length) {
        offset += courseImage.outerHeight();
      }
      if (courseProgress.length) {
        offset += courseProgress.outerHeight();
      }
      $('.single-mpcs-course .mpcs-sidebar-content').css('height', 'calc(100% - ' + offset + 'px');
    }
  });

})(jQuery);
