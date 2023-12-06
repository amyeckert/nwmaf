<?php if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');} ?>
<?php
use memberpress\courses\models as models;
use memberpress\courses\lib as lib;
?>
<h4 class="mpca-course-progress-heading"><?php esc_html_e('Course Progress for', 'memberpress-courses'); ?> <?php echo esc_html(lib\Utils::get_full_name( $user->ID )); ?> </h4>
  <?php if( ! empty($my_courses) ): ?>
  <?php foreach($my_courses as $course): ?>
    <div class="mpcs-course-information">
      <div class="course-progress-summary-row">
        <div class="course-progress-summary-title"><a href="<?php echo esc_url(get_the_permalink( $course->ID )) ; ?>"><?php echo esc_html( $course->post_title ); ?></a></div>
        <div class="course-progress-summary">
          <div class="course-progress">
            <div class="ca-user-progress" data-value="<?php echo esc_attr($course->user_progress($user->ID)); ?>"></div>
          </div>
        </div>
      </div>

      <?php foreach($course->quizzes() as $quiz){
          if( $quiz->post_status == 'draft' ){
            continue;
          }
          $has_completed_lesson = models\UserProgress::has_completed_lesson($user->ID, $quiz->ID);
          $attempt = $quiz->post_type == models\Quiz::$cpt ? models\Attempt::get_one(['user_id' => $user->ID, 'quiz_id' => $quiz->ID]) : false;
          $score = '-';
          if( $attempt instanceof models\Attempt && $has_completed_lesson ){
            $score = sprintf(
              /* translators: %1$s: points awarded, %2$s: points possible, %3$s: score percent, %%: literal percent sign */
              __('%1$s/%2$s (%3$s%%)', 'memberpress-courses'),
              $attempt->points_awarded,
              $attempt->points_possible,
              $attempt->score
            );
          }
          ?>
          <div class="quiz-progress-summary-row">
            <div class="quiz-progress-summary-title"><a href="<?php echo esc_url(get_the_permalink( $quiz->ID )) ; ?>"><?php echo esc_html($quiz->post_title); ?></a></div>
            <div class="quiz-progress-summary">
              <?php echo esc_html($score); ?>
            </div>
          </div>
    </div>
    <?php }
    endforeach;
    else: ?>
    <div class="mpcs-course-information">
       <p><?php esc_html_e('No records found.', 'memberpress-courses'); ?></p>
    </div>
  <?php endif;