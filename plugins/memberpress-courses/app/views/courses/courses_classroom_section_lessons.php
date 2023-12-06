<?php if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); } ?>
<?php use memberpress\courses\models as models; ?>

<?php foreach($sections as $section) : ?>
  <div id="section<?php echo ((int) $section->section_order + 1); ?>" class="mpcs-section">
    <div class="mpcs-section-header">
      <div class="mpcs-section-title">
        <span class="mpcs-section-title-text"><?php echo esc_html($section->title); ?></span>
      </div>
      <?php if(!empty($section->description)) : ?>
        <div class="mpcs-section-description"><?php echo esc_html($section->description); ?></div>
      <?php endif; ?>
    </div> <!-- mpcs-section-header -->
    <div class="mpcs-lessons">
      <?php foreach($section->lessons(false) as $lesson_index => $lesson) : ?>
        <?php
          $lesson_available = $lesson->is_available();
          $has_completed_lesson = is_user_logged_in() && models\UserProgress::has_completed_lesson($current_user_id, $lesson->ID);
          $attempt = $lesson->post_type == models\Quiz::$cpt ? models\Attempt::get_one(['user_id' => $current_user_id, 'quiz_id' => $lesson->ID]) : false;
        ?>
        <div id="mpcs-lesson-<?php echo esc_attr($lesson->ID); ?>" class="mpcs-lesson <?php
          if($has_completed_lesson) {
            echo "completed ";
          } else if(!$lesson_available || (get_post_type() == models\Quiz::$cpt && $lesson->ID != get_the_ID())) {
            echo "locked ";
          }
          if($lesson_available && $is_sidebar && $lesson->ID == get_the_ID()) echo "current ";
          if($show_bookmark && isset($next_lesson->ID) && $next_lesson->ID == $lesson->ID) echo "current ";
          ?>">

          <?php if($lesson_available) : ?>
            <a href="<?php echo esc_url(get_permalink($lesson->ID)); ?>" class="mpcs-lesson-row-link">
          <?php else: ?>
            <span class="mpcs-lesson-row-link">
          <?php endif; ?>
            <div class="mpcs-lesson-progress">
              <?php if($has_completed_lesson) : ?>
                <span class="mpcs-lesson-complete"><i class="mpcs-ok-circled"></i></span>
              <?php elseif($lesson_available && ($is_sidebar && $lesson->ID == get_the_ID()) || ($show_bookmark && $next_lesson->ID == $lesson->ID)) : ?>
                  <span class="mpcs-lesson-current"><i class="mpcs-adjust-solid"></i></span>
              <?php else: ?>
                <span class="mpcs-lesson-not-complete"><i class="mpcs-circle-regular"></i></span>
              <?php endif; ?>
            </div>
            <div class="mpcs-lesson-link">
              <i class="<?php echo esc_attr($lesson->post_type); ?>-icon"></i>
              <?php echo esc_html($lesson->post_title); ?>
              <?php if(!$is_sidebar && $has_completed_lesson && $attempt instanceof models\Attempt && $attempt->is_complete()) : ?>
                <span class="mpcs-lesson-list-quiz-score">(<?php echo esc_html($attempt->get_score_percent()); ?>)</span>
              <?php endif; ?>
            </div>
            <div class="mpcs-lesson-button">

            <?php if( is_user_logged_in() && false === $is_sidebar ) : ?>
              <span class="mpcs-button">
                <?php if($has_completed_lesson) : ?>
                  <span class="btn is-outline" href="<?php echo esc_url(get_permalink($lesson->ID)); ?>">
                    <?php esc_html_e('View', 'memberpress-courses') ?>
                  </span>
                <?php elseif($lesson_available) : ?>
                  <span class="btn btn-green is-purple" href="<?php echo esc_url(get_permalink($lesson->ID)); ?>">
                    <?php if($attempt instanceof models\Attempt && $attempt->is_draft()) : ?>
                      <?php esc_html_e('Continue', 'memberpress-courses') ?>
                    <?php else : ?>
                      <?php esc_html_e('Start', 'memberpress-courses') ?>
                    <?php endif; ?>
                  </span>
                <?php endif; ?>
              </span>
            <?php endif; ?>

            </div>
          <?php if($lesson_available) : ?>
            </a>
          <?php else: ?>
          </span>
          <span class="mpcs-lesson-locked-tooltip"><?php esc_html_e( 'Lesson unavailable. You must complete all previous lessons and quizzes before you start this lesson.', 'memberpress-courses' ); ?></span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div> <!-- mpcs-lessons -->
  </div> <!-- mpcs-section -->
<?php endforeach; ?>
