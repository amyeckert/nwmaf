<?php
namespace memberpress\courses\helpers;
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses\models as models;
use memberpress\courses\lib as lib;

class Lessons {
  /**
  * Href for lesson
  * @param int ID of the current lesson
  * @return (string|false)
  */
  public static function lesson_link($lesson_id) {
    return get_permalink($lesson_id);
  }

  /**
  * Href for course
  * @param int ID of the current course
  * @return (string|false)
  */
  public static function course_link($course_id) {
    return get_permalink($course_id);
  }

  /**
  * Check if current lesson is the first lesson
  * @param int $current_lesson_index Index of the current lesson
  * @return boolean
  */
  public static function has_previous_lesson($current_lesson_index) {
    return $current_lesson_index > 0;
  }

  /**
  * Check if section has next lesson
  * @param int $current_lesson_index Index of the current lesson
  * @param array[int] $lesson_nav_ids Array of lesson ids for current section
  * @return boolean
  */
  public static function has_next_lesson($current_lesson_index, $lesson_nav_ids) {
    return isset($lesson_nav_ids[$current_lesson_index + 1]);
  }

  /**
  * Check if course has next section
  * @param int $current_section_index Index of the current section
  * @param array[int] $section_ids Array of section ids for current course
  * @return boolean
  */
  public static function has_previous_section($current_section_index) {
    return $current_section_index > 0;
  }

  /**
  * Check if course has next section
  * @param int $current_section_index Index of the current section
  * @param array[int] $section_ids Array of section ids for current course
  * @return boolean
  */
  public static function has_next_section($current_section_index, $section_ids) {
    return isset($section_ids[$current_section_index + 1]);
  }

  /**
  * Href for previous lesson
  * @param int $current_lesson_index Index of the current lesson
  * @param array[int] $lesson_nav_ids Array of lesson ids for current section
  * @return (string|false)
  */
  public static function previous_lesson_link($current_lesson_index, $lesson_nav_ids) {
    $previous_lesson_id = $lesson_nav_ids[$current_lesson_index - 1];

    return get_permalink($previous_lesson_id);
  }

  /**
  * Href for next lesson
  * @param int $current_lesson_index Index of the current lesson
  * @param array[int] $lesson_nav_ids Array of lesson ids for current section
  * @return (string|false)
  */
  public static function next_lesson_link($current_lesson_index, $lesson_nav_ids) {
    $next_lesson_id = $lesson_nav_ids[$current_lesson_index + 1];

    return get_permalink($next_lesson_id);
  }

  /**
  * Href for next section
  * @param int $current_section_index Index of the current section
  * @param array[int] $section_ids Array of section ids for current course
  * @return (string|false)
  */
  public static function previous_section_link($current_section_index, $section_ids) {
    $previous_section = new models\Section($section_ids[$current_section_index - 1]);
    $previous_section_lessons = $previous_section->lessons();
    $previous_lesson = end($previous_section_lessons);

    return get_permalink($previous_lesson->ID);
  }

  /**
  * Href for next section
  * @param int $current_section_index Index of the current section
  * @param array[int] $section_ids Array of section ids for current course
  * @return (string|false)
  */
  public static function next_section_link($current_section_index, $section_ids) {
    $permalink = '';
    $next_section = new models\Section($section_ids[$current_section_index + 1]);
    $next_section_lessons = $next_section->lessons();

    if($next_section_lessons){
      $next_lesson = $next_section_lessons[0];
      $permalink = get_permalink($next_lesson->ID);
    }

    return $permalink;
  }

  /**
  * Href for section's first lesson
  * @param int $section_id
  * @return (string|false)
  */
  public static function section_link($section_id) {
    $section = new models\Section($section_id);
    $course = $section->course();
    if(empty($course)) {
      return '#';
    }
    else {
      return get_permalink($course->ID) . '#section' . (string)((int)$section->section_order + 1);
    }
  }

  /**
   * Checks if current post is a lesson or a quiz
   *
   * @param  \WP_Post $post
   * @return bool
   */
  public static function is_a_lesson($post) {
    return isset($post) && is_a($post, 'WP_Post') && in_array($post->post_type, array(models\Lesson::$cpt, models\Quiz::$cpt), true);
  }

  /**
   * Get the lesson or quiz instance for the given post
   *
   * @param  \WP_Post $post
   * @return models\Lesson|models\Quiz|null
   */
  public static function get_lesson($post) {
    if($post instanceof \WP_Post) {
      if($post->post_type == models\Lesson::$cpt) {
        return new models\Lesson($post->ID);
      }
      elseif($post->post_type == models\Quiz::$cpt) {
        return new models\Quiz($post->ID);
      }
    }

    return null;
  }

  /**
   * Get permalink base slug
   *
   * @return void
   */
  public static function get_permalink_base(){
    $slug = models\Lesson::$permalink_slug;
    $options = \get_option('mpcs-options');

    if(!empty(Options::val($options,'lessons-slug'))){
      $slug = Options::val($options,'lessons-slug');
    }

    return $slug;
  }

  /**
   * Display lesson menu
   *
   * @param  object $post
   * @return mixed
   */
  public static function display_lesson_buttons($post){
    $current_user = lib\Utils::get_currentuserinfo();
    $current_lesson = new models\Lesson($post->ID);
    $lesson_nav_ids = $current_lesson->nav_ids();
    $current_lesson_index = \array_search($current_lesson->ID, $lesson_nav_ids);
    $current_section = $current_lesson->section();
    $lesson_available = $current_lesson->is_available();

    if($current_section !== false && $lesson_available) {
      if(!self::has_next_lesson($current_lesson_index, $lesson_nav_ids) || !self::has_previous_lesson($current_lesson_index, $lesson_nav_ids)) {
        $current_course = $current_section->course();
        $sections = $current_course->sections();
        $section_ids = \array_map(function($section) {
          return $section->id;
        }, $sections);
        $current_section_index = \array_search($current_section->id, $section_ids);
      }

      $options = \get_option('mpcs-options');

      \ob_start();

      if($post->post_type == models\Quiz::$cpt && is_user_logged_in()) {
        $attempt = models\Attempt::get_one(['quiz_id' => $post->ID, 'user_id' => get_current_user_id()]);

        require \MeprView::file('/quizzes/courses_classroom_buttons');
      }
      else {
        require \MeprView::file('/lessons/courses_classroom_buttons');
      }

      $nav_links = \ob_get_clean();

      return $nav_links;
    }
  }
}
