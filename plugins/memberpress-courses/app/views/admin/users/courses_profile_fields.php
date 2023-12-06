<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<?php use memberpress\courses\models as models; ?>
<h3><?php _e('Course Information', 'memberpress-courses'); ?></h3>
<table class="form-table mpcs-course-information">
  <?php foreach($my_courses as $course): ?>
    <tr>
      <th><a class="mpca-course-progress-title" target="_blank" href="<?php echo esc_url(add_query_arg(['post' => $course->ID], admin_url('post.php?action=edit'))); ?>"><?php echo $course->post_title; ?></a></th>
      <td class="progress">
        <div class="course-progress">
          <div class="user-progress" data-value="<?php echo $course->user_progress($user->ID); ?>">
          </div>
        </div>
      </td>
      <td>
      <?php if($course->user_progress($user->ID) > 0){ ?>
        <a class="mpcs-reset-course-progress" data-value="<?php echo $course->ID; ?>" data-user="<?php echo (int) $_GET['user_id']; ?>" data-nonce="<?php echo wp_create_nonce('reset_progress') ?>" href="#0"><?php _e('Reset Progress', 'memberpress-courses'); ?></a>
      <?php } ?>
      </td>
    </tr>
    <?php foreach($course->quizzes() as $quiz){
        if( $quiz->post_status == 'draft' ){
          continue;
        }
        $has_completed_lesson = models\UserProgress::has_completed_lesson($user->ID, $quiz->ID);
        $attempt = $quiz->post_type == models\Quiz::$cpt ? models\Attempt::get_one(['user_id' => $user->ID, 'quiz_id' => $quiz->ID]) : false;
        $score = '-';
        $view_attempt = esc_attr__('No Attempt', 'memberpress-courses');
        if( $attempt instanceof models\Attempt && $has_completed_lesson ){
          $score = sprintf(
            /* translators: %1$s: points awarded, %2$s: points possible, %3$s: score percent, %%: literal percent sign */
            __('%1$s/%2$s (%3$s%%)', 'memberpress-courses'),
            $attempt->points_awarded,
            $attempt->points_possible,
            $attempt->score
          );
          $view_attempt = sprintf(
            '<a href class="mpcs-quiz-attempt-view mpcs-quiz-attempt" data-id="%s">%s</a>',
            esc_attr($attempt->id),
            esc_attr__('View Attempt', 'memberpress-courses')
          );
        }
        ?>
        <tr>
          <th><div class="mpca-quiz-progress-title"><a target="_blank" href="<?php echo esc_url(add_query_arg(['id' => $quiz->ID], admin_url('admin.php?page=mpcs-quiz-attempts'))); ?>"><?php echo esc_html($quiz->post_title); ?></a></div></th>
          <td class="progress mpca-quiz-progress"><?php echo $score; ?></td>
          <td><?php echo $view_attempt; ?>
          </td>
        </tr>
    <?php }
    endforeach; ?>
</table>
