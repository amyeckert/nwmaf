<?php
namespace memberpress\courses\controllers;

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses as base;
use memberpress\courses\lib as lib;
use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

class Lessons extends lib\BaseCtrl {
  public function load_hooks() {
    add_filter('the_content', array($this, 'prepend_breadcrumbs'));
    add_filter('the_content', array($this, 'append_lesson_navigation'));
    add_filter('the_content', array($this, 'lesson_locked_message'), 99);
    if(helpers\App::is_classroom()) {
      add_filter( 'template_include', array( $this, 'override_template' ), 999999 );
    } else {
      add_filter( 'template_include', array( $this, 'override_template' ));
    }
    add_filter('body_class', array($this, 'add_body_class'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 99999999);
    add_action('wp_ajax_mpcs_record_lesson_progress', array($this, 'record_lesson_progress'));
    add_filter('embed_oembed_html', array($this, 'wrap_oembed_html'), 99, 2);
    add_action('save_post', array($this, 'delete_transients'), 10, 2 );
  }

  /**
  * Prepend the section breadcrumb navigation to the content
  * @see load_hooks(), add_filter('the_content')
  * @param string $content The post content
  * @return string $content The modified post content
  */
  public static function prepend_breadcrumbs($content) {
    global $post;

    if(is_single() && ! helpers\App::is_classroom()) {
      if(isset($post) && is_a($post, 'WP_Post') && in_array($post->post_type, models\Lesson::lesson_cpts())) {
        $current_lesson = new models\Lesson($post->ID);
        $current_section = $current_lesson->section();
        if($current_section !== false) {
          $current_course = $current_section->course();
          $options = \get_option('mpcs-options');
          \ob_start();
            require(\MeprView::file('/lessons/courses_breadcrumbs'));
          $breadcumbs = \ob_get_clean();

          $content = $breadcumbs . $content;
        }
      }
    }

    return $content;
  }

  /**
  * Append the course navigation to the content
  * @see load_hooks(), add_filter('the_content')
  * @param string $content The post content
  * @return string The modified post content
  */
  public static function append_lesson_navigation($content) {
    global $post;

    if($post instanceof \WP_Post && is_single() && in_array($post->post_type, models\Lesson::lesson_cpts(), true)) {
      $options = \get_option('mpcs-options');
      $show_buttons = !helpers\App::is_classroom() || helpers\Options::val($options,'lesson-button-location', 'top') != 'top';
      $is_lesson_or_logged_out_quiz = $post->post_type == models\Lesson::$cpt || (!is_user_logged_in() && $post->post_type == models\Quiz::$cpt);

      if($show_buttons && $is_lesson_or_logged_out_quiz) {
        $current_user = lib\Utils::get_currentuserinfo();
        $current_lesson = new models\Lesson($post->ID);
        $lesson_nav_ids = $current_lesson->nav_ids();
        $current_lesson_index = \array_search($current_lesson->ID, $lesson_nav_ids);
        $current_section = $current_lesson->section();
        $lesson_available = $current_lesson->is_available();

        if($current_section !== false && $lesson_available) {
          if(!helpers\Lessons::has_next_lesson($current_lesson_index, $lesson_nav_ids)) {
            $current_course = $current_section->course();
            $sections = $current_course->sections();
            $section_ids = \array_map(function ($section) {
              return $section->id;
            }, $sections);
            $current_section_index = \array_search($current_section->id, $section_ids);
          }

          \ob_start();
          require \MeprView::file('/lessons/courses_navigation');
          $nav_links = \ob_get_clean();

          $content .= $nav_links;
        }
      }
    }

    return $content;
  }

  public static function lesson_locked_message($content) {
    global $post;

    if(is_single() && !helpers\App::is_classroom()) {
      if(isset($post) && is_a($post, 'WP_Post') && in_array($post->post_type, models\Lesson::lesson_cpts(), true)) {
        $current_user = lib\Utils::get_currentuserinfo();
        $lesson = new models\Lesson($post->ID);
        $lesson_available = $lesson->is_available();
        $button_class = 'mpcs-button is-purple';

        //If the lesson is not available. Replace the content with our message.
        if(!$lesson_available) {
          \ob_start();
            require(\MeprView::file('/lessons/lesson_locked'));
          $content = \ob_get_clean();
        }
      }
    }

    return $content;
  }

  /**
  * Override default template with the lesson page template
   *
  * @param string $template current template
  * @return string modified template
  */
  public static function override_template($template) {
    global $post;

    if($post instanceof \WP_Post && $post->post_type == models\Lesson::$cpt && is_single()) {
      $lesson = new models\Lesson($post->ID);
      $course = $lesson->course();

      if($course instanceof models\Course) {
        $new_template = locate_template($course->page_template);
      }
      else {
        // If the course is not found, trigger a 404 error
        global $wp_query;
        $wp_query->set_404();

        status_header(404);
        nocache_headers();

        return get_404_template();
      }

      if(helpers\App::is_classroom()) {
        $template = \MeprView::file('/classroom/courses_single_lesson');
      }
      elseif(isset($new_template) && !empty($new_template)) {
        return $new_template;
      }
      else {
        $template = locate_template(
          array(
            'single-mpcs-lesson.php',
            'single-mpcs-course.php',
            'page.php',
            'custom_template.php',
            'single.php',
            'index.php'
          )
        );
      }
    }

    return $template;
  }

  /**
   * Add class to body lesson
   * @param array $classes
   * @return array
   */
  public function add_body_class($classes) {
    global $post;

    if($post instanceof \WP_Post && in_array($post->post_type, models\Lesson::lesson_cpts(), true)) {
      $lesson = helpers\Lessons::get_lesson($post);

      if($lesson instanceof models\Lesson) {
        $section = $lesson->section();

        if($section instanceof models\Section) {
          $course = $section->course();

          if($course->accordion_sidebar === 'enabled') {
              $classes[] = 'mpcs-sidebar-with-accordion';
          }
        }
      }
    }

    return $classes;
  }

  /**
  * Enqueue scripts for lessons controller
  * @see load_hooks(), add_action('wp_enqueue_scripts')
  */
  public static function enqueue_scripts() {
    global $post;

    if( is_a($post, 'WP_Post') && is_single() && in_array($post->post_type, models\Lesson::lesson_cpts())) {
      $locals = array(
        'ajaxurl' => \admin_url('admin-ajax.php'),
        'progress_nonce' => \wp_create_nonce('lesson_progress'),
      );

      if ( !helpers\App::is_classroom() ) {
        \wp_enqueue_style('mpcs-fontello-styles', base\FONTS_URL.'/fontello/css/mp-courses.css', array(), base\VERSION);
        \wp_enqueue_style('mpcs-lesson-css', base\CSS_URL . '/lesson.css', array(), base\VERSION);
        \wp_enqueue_script('mpcs-lesson', base\JS_URL . '/lesson.js', array('jquery'), base\VERSION);
        \wp_localize_script('mpcs-lesson', 'mpcs_locals', $locals);
        return;
      }

      Classroom::remove_styles(array(
        'global-styles',
        'wp-block-library',
        'wp-block-library-theme',
        'mpcs-fontello-styles',
        'mpcs-quiz'
      ));

      \wp_enqueue_style('mpcs-fontello-styles', base\FONTS_URL.'/fontello/css/mp-courses.css', array(), base\VERSION);
      \wp_enqueue_style('mpcs-lesson-css', base\CSS_URL . '/lesson.css', array(), base\VERSION);
      \wp_enqueue_script('mpcs-lesson', base\JS_URL . '/lesson.js', array('jquery'), base\VERSION);
      // Make ajaxurl available to JS
      \wp_localize_script('mpcs-lesson', 'mpcs_locals', $locals);

      \wp_enqueue_style('mpcs-classroom', base\CSS_URL . '/classroom.css', array(), base\VERSION);
      \wp_enqueue_script('mpcs-classroom-js', base\JS_URL . '/classroom.js', array('jquery'), base\VERSION);
    }
  }

  /**
  * Record user_progress record for lesson
  * @see load_hooks(), add_action('wp_ajax_mpcs_record_lesson_progress')
  * @return void
  */
  public static function record_lesson_progress() {
    lib\Utils::check_ajax_referer('lesson_progress', 'progress_nonce');
    $current_user = lib\Utils::get_currentuserinfo();

    if(!is_user_logged_in()) {
      lib\Utils::exit_with_status(403, json_encode(array('error' => __('Forbidden', 'memberpress-courses'))));
    }

    if ( \MeprRule::is_locked( get_post( $_POST['lesson_id'] ) ) ) {
      lib\Utils::exit_with_status(403, json_encode(array('error' => __('Unauthorized', 'memberpress-courses'))));
    }

    try {
      lib\Validate::not_null($_POST['lesson_id'], 'lesson_id');
      lib\Validate::is_numeric($_POST['lesson_id'], 1, null, 'lesson_id');

      $lesson = new models\Lesson($_POST['lesson_id']);

      lib\Validate::is_numeric($lesson->ID, 1, null, 'lesson_id');
    }
    catch(lib\ValidationException $e) {
      lib\Utils::exit_with_status(403, json_encode(array('error' => $e->getMessage())));
    }

    $user_progress = models\UserProgress::find_one_by_user_and_lesson($current_user->ID, $lesson->ID);

    // TODO: In the future we may want to update the percent of progress for
    // the record if it's found and not necessarily fail here
    if(!empty($user_progress) && !empty($user_progress->id)) {
      lib\Utils::exit_with_status(403, json_encode(array('error' => __('This lesson has already been completed', 'memberpress-courses'))));
    }

    $lesson->complete($current_user->ID);

    lib\Utils::exit_with_status(200, json_encode(array('message' => __('Progress was recorded for this User and Lesson', 'memberpress-courses'))));
  }

  /**
   * Display classroom navigation
   *
   * @param  mixed $post
   * @return void
   */
  public static function display_classroom_navigation($post){
    $current_user = lib\Utils::get_currentuserinfo();
    $current_lesson = new models\Lesson($post->ID);
    $lesson_nav_ids = $current_lesson->nav_ids();
    $current_lesson_index = \array_search($current_lesson->ID, $lesson_nav_ids);
    $current_section = $current_lesson->section();

    if($current_section == false) {
      return;
    }

    if(!helpers\Lessons::has_next_lesson($current_lesson_index, $lesson_nav_ids)) {
      $current_course = $current_section->course();
      $sections = $current_course->sections();
      $section_ids = \array_map(function($section) {
        return $section->id;
      }, $sections);
      $current_section_index = \array_search($current_section->id, $section_ids);
    }

    $options = \get_option('mpcs-options');

    \ob_start();
      require(\MeprView::file('/lessons/classroom/courses_navigation'));
    $nav_links = \ob_get_clean();

    return $nav_links;
  }

  /**
   * Add html wrapper to oembed_html
   *
   * @param  string $html
   * @param  string $url
   * @return string
   */
  public function wrap_oembed_html($html, $url) {
    if( !helpers\App::is_classroom() )
      return $html;

    $providers = array('vimeo.com', 'youtube.com', 'youtu.be', 'wistia.com', 'wistia.net');
    $found = array_filter($providers, function($provider) use ($url){
      return false !== strpos( $url, $provider);
    });

    if ( $found ) {
      $html = '<div class="responsive-video">' . $html . '</div>';
    }
    return $html;
  }

  /**
   * Delete Transients
   *
   * @param  mixed $new_status
   * @param  mixed $old_status
   * @param  mixed $post
   * @return void
   */
  function delete_transients( $post_id, $post ){
    if ( models\Lesson::$cpt !== $post->post_type )
      return; // restrict the filter to a specific post type

    // let's get and delete transients
    $transients = \get_option('mpcs-transients', array());
    foreach ($transients as $transient) {
      delete_transient( $transient );
    }
    \delete_option('mpcs-transients');
  }
}
