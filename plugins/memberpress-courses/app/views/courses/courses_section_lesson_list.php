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
      <?php foreach($section->lessons() as $lesson_index => $lesson) : ?>
        <?php
          $lesson_available = $lesson->is_available();
          $has_completed_lesson = is_user_logged_in() && models\UserProgress::has_completed_lesson($current_user_id, $lesson->ID);
          $attempt = $lesson->post_type == models\Quiz::$cpt ? models\Attempt::get_one(['user_id' => $current_user_id, 'quiz_id' => $lesson->ID]) : false;
        ?>
        <div id="mpcs-lesson-<?php echo esc_attr($lesson->ID); ?>" class="mpcs-lesson <?php if(!$lesson_available) { echo "locked "; } ?>">
          <?php if($lesson_available) : ?>
            <a href="<?php echo esc_url(get_permalink($lesson->ID)); ?>" class="mpcs-lesson-row-link">
          <?php else: ?>
            <span class="mpcs-lesson-row-link">
          <?php endif; ?>
            <div class="mpcs-lesson-progress">
              <?php if($has_completed_lesson) : ?>
                <span class="mpcs-lesson-complete"><i class="mpcs-ok-circled"></i></span>
              <?php elseif($lesson_available) : ?>
                <span class="mpcs-lesson-not-complete"><i class="mpcs-circle-regular"></i></span>
              <?php else: ?>
                <span class="mpcs-lesson-locked"><i class="mpcs-circle-regular"></i></span>
              <?php endif; ?>
            </div>
            <div class="mpcs-lesson-link">
              <?php echo esc_html($lesson->post_title); ?>
              <?php if($has_completed_lesson && $attempt instanceof models\Attempt && $attempt->is_complete()) : ?>
                <span class="mpcs-lesson-list-quiz-score">(<?php echo esc_html($attempt->get_score_percent()); ?>)</span>
              <?php endif; ?>
            </div>
            <div class="mpcs-lesson-button">
            <span class="mpcs-button" href="<?php echo esc_url(get_permalink($lesson->ID)); ?>">
              <?php if($has_completed_lesson) : ?>
                <span class="mpcs-button is-outline" href="<?php echo esc_url(get_permalink($lesson->ID)); ?>">
                  <?php esc_html_e('View', 'memberpress-courses') ?>
                </span>
              <?php elseif($lesson_available) : ?>
                <span class="mpcs-button is-purple" href="<?php echo esc_url(get_permalink($lesson->ID)); ?>">
                  <?php if($attempt instanceof models\Attempt && $attempt->is_draft()) : ?>
                    <?php esc_html_e('Continue', 'memberpress-courses') ?>
                  <?php else : ?>
                    <?php esc_html_e('Start', 'memberpress-courses') ?>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </span>
            </div>
          <?php if($lesson_available) : ?>
            </a>
          <?php else: ?>
            </span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div> <!-- mpcs-lessons -->
  </div> <!-- mpcs-section -->
<?php endforeach; ?>
