(function($) {
  $(document).ready(function() {
    $('body').on('click', '.admin-tooltip', function() {
      var tooltip_title = $(this).find('.data-title').html();
      var tooltip_info = $(this).find('.data-info').html();
      var edge = $(this).data('edge') || 'left';

      $(this).pointer({
        content: '<h3>' + tooltip_title + '</h3><p>' + tooltip_info + '</p>',
        position: {
          edge: edge,
          align: 'center'
        }
      })
      .pointer('open');
    });
  });
})(jQuery);
