<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses as base;
use memberpress\courses\helpers as helpers;
use memberpress\courses\models as models;
?>
<?php
$current_user = wp_get_current_user();
$options = \get_option('mpcs-options');
$remove_instructor_link = helpers\Options::val($options, 'remove-instructor-link');
$course = new base\models\Course($post->ID);
$progress = $course->user_progress($current_user->ID);
?>
<div class="mpcs-sidebar-wrapper">

  <?php do_action(base\SLUG_KEY . '_classroom_start_sidebar'); ?>

  <?php echo helpers\Lessons::is_a_lesson($post) ? '<div id="mpcs-sidebar-header">' : '' ?>

    <!-- Featured Image -->
    <?php if ( ! empty( models\Lesson::get_thumbnail($post) ) ) : ?>
      <figure class="figure">
        <a href="<?php the_permalink(); ?>" alt="<?php the_title_attribute(); ?>">
          <img src="<?php echo esc_url(models\Lesson::get_thumbnail($post)) ?>" alt="">
        </a>
      </figure>
    <?php endif; ?>

    <!-- Progress -->
    <div class="course-progress">
      <?php echo helpers\Courses::classroom_sidebar_progress($post); ?>
    </div>

  <?php echo helpers\Lessons::is_a_lesson($post) ? '</div>' : '' ?>

  <div class="mpcs-sidebar-content">

    <!-- Menu -->
    <?php
      if(helpers\Lessons::is_a_lesson($post)) {
        echo helpers\Courses::display_course_overview(false, true);
      }

      if(helpers\Courses::is_a_course($post)){ ?>
        <div class="section mpcs-sidebar-menu">
          <a class="tile <?php \MeprAccountHelper::active_nav('home', 'is-active') ?>" href="<?php echo get_permalink() ?>">
            <div class="tile-icon">
              <i class="mpcs-list-alt"></i>
            </div>
            <div class="tile-content">
              <p class="tile-title m-0"><?php esc_html_e('Course Overview', 'memberpress-courses') ?></p>
            </div>
          </a>
        <?php if ( $course->user_progress( $current_user->ID ) >= 100 && $course->certificates_enable == 'enabled' ) { ?>
            <?php
            $cert_url = admin_url( 'admin-ajax.php?action=mpcs-course-certificate' );
            $cert_url = add_query_arg(
              array(
                'user' => $current_user->ID,
                'course' => $post->ID,
              ),
              $cert_url
            );
            $share_link = add_query_arg(
              array(
                'shareable' => 'true',
              ),
              $cert_url
            );
            ?>
        <a target="_blank" class="tile <?php \MeprAccountHelper::active_nav('certificate', 'is-active') ?>" href="<?php echo esc_url_raw($cert_url); ?>">
          <div class="tile-icon">
            <i class="mpcs-award"></i>
          </div>
          <div class="tile-content">
            <p class="tile-title m-0"><?php esc_html_e('Certificate', 'memberpress-courses') ?>
              <?php if ($course->certificates_share_link == 'enabled') { ?><i title="<?php echo esc_attr_e('Copied Shareable Certificate Link', 'memberpress-courses') ?>" class="mpcs-share" data-clipboard-text="<?php echo esc_url_raw($share_link); ?>" onclick="return false;"></i><?php } ?>
            </p>
          </div>
        </a>
        <?php } ?>
          <?php if (empty($remove_instructor_link)) { ?>
          <a class="tile <?php \MeprAccountHelper::active_nav('instructor', 'is-active') ?>" href="<?php echo get_permalink() . '?action=instructor' ?>">
            <div class="tile-icon">
              <i class="mpcs-user"></i>
            </div>
            <div class="tile-content">
              <p class="tile-title m-0"><?php esc_html_e('Your Instructor', 'memberpress-courses') ?></p>
            </div>
          </a>
          <?php } ?>
        </div>
        <?php
      }
      ?>

    <?php if ( is_active_sidebar( 'mpcs_classroom_sidebar' ) ) : ?>
      <div id="primary-sidebar" class="primary-sidebar widget-area" role="complementary">
        <?php dynamic_sidebar( 'mpcs_classroom_sidebar' ); ?>
      </div>
    <?php endif; ?>
    <?php do_action(base\SLUG_KEY . '_classroom_end_sidebar'); ?>
  </div>
</div>
