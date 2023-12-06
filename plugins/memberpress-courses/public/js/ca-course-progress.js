(function($) {
  $(document).ready(function() {

    $(document).on('keyup', function(e) {
      var key = event.key || event.keyCode;
      if (key === 'Escape' || key === 'Esc' || key === 27) {
          $('.course-progress-close-button').trigger('click');
      }
    });

    $('.course-progress-close-button').on('click', function(e) {
      let course_progress_modal = $('#course-progress-modal');
      course_progress_modal.removeClass('on');
      $('body').removeClass('mpca_progress_on');
    });

    $('.mpca-course-sub-account-progress').on('click', function(e) {
      e.preventDefault();

      var oThis = $(this);
      let course_progress_modal = $('#course-progress-modal');

      let mpca_subaccount_progress = $('#mpca-subaccount-progress');
      let params = {
        action:  'mpcs_ca_view_course_progress',
        ca:      $(this).data('ca'),
        sa:      $(this).data('sa'),
        nonce:   mpca_progress.nonce
      };
      course_progress_modal.removeClass('on');
      $.post(mpca_progress.ajaxurl, params, function(res) {
        mpca_subaccount_progress.html(res);
        course_progress_modal.addClass('on');
        $('body').addClass('mpca_progress_on');
        $('.course-progress').each(function(i, e) {
          var progress_bar = $('.ca-user-progress', e);
          var progress = 0;
          var interval = setInterval(expand_progress, 10);
          var target_progress = progress_bar.data('value');
          progress_bar.html('<span>'+target_progress + '&#37;</span>');
          function expand_progress() {
              if (progress >= target_progress) {
                  clearInterval(interval);
              } else {
                  progress++;
                  progress_bar.width(progress + '%');
              }
          }
        });
      });

    });
  });
})(jQuery);
