<?php
namespace memberpress\courses\models;

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses as base;
use memberpress\courses\lib as lib;
use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

/**
 * @property string $status The status
 * @property int $section_id The section ID
 * @property int $lesson_order The lesson order
 */
class Lesson extends lib\BaseCptModel {
  public static $cpt = 'mpcs-lesson';
  public static $nonce_str = 'mpcs-lesson-nonce';
  public static $permalink_slug = 'lessons';
  public static $section_id_str = '_mpcs_lesson_section_id';
  public static $lesson_order_str = '_mpcs_lesson_lesson_order';
  public $statuses;

  public function __construct($obj = null) {
    parent::__construct($obj);
    $this->load_cpt(
      $obj,
      self::$cpt,
      array(
        'status'        => array( 'default' => 'enabled', 'type' => 'string' ),
        'section_id'    => array( 'default' => 0,         'type' => 'integer' ),
        'lesson_order'  => array( 'default' => 0,         'type' => 'integer' )
      )
    );

    $this->statuses = array(
      'enabled',
      'disabled'
    );
  }

  public function validate() {
    lib\Validate::is_in_array($this->status, $this->statuses, 'status');
  }

  /**
   * Get the section for this lesson
   *
   * @return Section|false
   */
  public function section() {
    return models\Section::find($this->section_id);
  }

  /**
   * Get the course for this lesson
   *
   * @return Course|false
   */
  public function course() {
    if($section = $this->section()) {
      if($course = $section->course()) {
        return $course;
      }
    }

    return false;
  }
  /**
  * Get ids of ordered surrounding pages
  * @return array[int] Array of lesson ids
  */
  public function nav_ids() {
    $query = new \WP_Query(
      array(
        'post_type'  => Lesson::lesson_cpts(),
        'meta_query' => array(
          array(
            'key'   => Lesson::$section_id_str,
            'value' => $this->section_id,
          ),
          array(
            'key'     => Lesson::$lesson_order_str,
            'value'   => array($this->lesson_order - 1, $this->lesson_order + 1),
            'type'    => 'numeric',
            'compare' => 'BETWEEN',
          )
        ),
        'meta_key'  => Lesson::$lesson_order_str,
        'orderby'  => 'meta_value_num',
        'order'     => 'ASC',
        'fields'    => 'ids',
      )
    );

    return $query->posts;
  }

  public function cloneit() {
    $lesson_dup = (array) $this->rec;
    $unset_keys = array('ID', 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'guid');
    $lesson_dup = \array_diff_key($lesson_dup, \array_flip($unset_keys));
    $lesson_dup['post_title'] .= ' (' . __('Copy', 'memberpress-courses') . (string) time() . ')';

    return \wp_insert_post($lesson_dup);
  }

  public static function lesson_cpts() {
    return array(self::$cpt,
                 Quiz::$cpt);
  }

  public static function find_all_by_section($section_id,$post_types=null,$include_private=true) {
    global $wpdb;
    static $section_lessons = array();

    if($post_types == null || ! is_array($post_types) || empty($post_types)){
      $post_types = Lesson::lesson_cpts();
    }
    $post_types_string = implode("','", $post_types);

    $post_statuses = array('trash');
    if(!$include_private) {
      $post_statuses[] = 'private';
    }
    $post_statuses_string = implode("','", $post_statuses);

    $query = $wpdb->prepare("
      SELECT ID, post_type FROM {$wpdb->posts} AS p
        JOIN {$wpdb->postmeta} AS pm
          ON p.ID = pm.post_id
         AND pm.meta_key = %s
         AND pm.meta_value = %s
        JOIN {$wpdb->postmeta} AS pm_order
          ON p.ID = pm_order.post_id
         AND pm_order.meta_key = %s
       WHERE p.post_type in ('" . $post_types_string . "') AND p.post_status NOT IN ('". $post_statuses_string ."')
       ORDER BY pm_order.meta_value * 1
       ",
       Lesson::$section_id_str,
       $section_id,
       Lesson::$lesson_order_str
    );

    $key_query = md5($query);
    if( ! isset($section_lessons[$key_query]) ) {
      $section_lessons[$key_query] = $wpdb->get_results($query);
    }

    $lessons = array();

    foreach($section_lessons[$key_query] as $lesson) {
      if($lesson->post_type == Quiz::$cpt) {
        $lessons[] = new Quiz($lesson->ID);
      }
      else {
        $lessons[] = new Lesson($lesson->ID);
      }
    }

    return $lessons;
  }


  public static function find_all($options = array()) {
    $lessons = Lesson::get_all_objects($options);

    if($lessons === false) {
      $lessons = array();
    }

    return $lessons;
  }

  public static function exists($lesson_id, $section_id) {
    $lesson = Lesson::get_one(array(
      'wheres' => array(
        'ID' => $lesson_id,
        'section_id' => $section_id,
      )
    ));

    return (isset($lesson) && $lesson instanceof Lesson) ? true : false;
  }

  public function add_to_section($section_id, $order) {
    $this->section_id = $section_id;
    $this->lesson_order = $order;
    $this->store_meta();
  }

  public function update_order($order) {
    $this->lesson_order = $order;
    $this->store_meta();
  }

  public function remove_from_section() {
    delete_post_meta($this->ID, Lesson::$section_id_str, $this->section_id);
    delete_post_meta($this->ID, Lesson::$lesson_order_str, $this->lesson_order);
  }

  public function get_previous_lesson() {
    $course = $this->course();

    if($course instanceof models\Course) {
      $previous_lesson = false;

      foreach($course->sections() as $section) {
        foreach($section->lessons() as $lesson) {
          if($lesson->ID == $this->ID) {
            return $previous_lesson;
          }

          $previous_lesson = $lesson;
        }
      }
    }

    return false;
  }

  public function is_available() {
    if(!is_user_logged_in()) {
      return true;
    }

    // Allow access to users who can edit this lesson, so they can preview it
    if(current_user_can('edit_post', $this->ID)) {
      return true;
    }

    // Allow access to completed lessons
    if(UserProgress::has_completed_lesson(get_current_user_id(), $this->ID)) {
      return true;
    }

    $section = $this->section();
    $course = $this->course();

    //We need all this info to continue
    if($section && $course) {
      //Previous lesson is required
      if($course->require_previous == 'enabled') {
        if($previous_lesson = $this->get_previous_lesson()) {
          //If we have a previous lesson, look to see if it is complete
          if(!UserProgress::has_completed_lesson(get_current_user_id(), $previous_lesson->ID)) {
            //previous lesson is required but not completed
            return false;
          }
        }
      }
    }

    //If we have reached this point, the lesson is available
    return true;
  }

  protected function load_cpt_from_id($id) {
    $post = (array)get_post($id);

    //Doing this so that Quiz can extend Lesson. So a quiz can appear as a Lesson unless we specifically need it to be a quiz.
    //For example, in UserProgress
    if( null === $post || (isset($post['post_type']) && !in_array($post['post_type'], self::lesson_cpts())) ) {
      //error_log('load_cpt_from_id didn\'t find lesson ID='.$id);
    }
    else {
      $this->rec = (object)array_merge((array)$this->rec,(array)$post);
      $this->load_meta($id);
    }
  }

  /**
   * Has this lesson been completed?
   *
   * @param int|null $user_id The user ID. If null, defaults to the ID of the currently logged-in user
   * @return bool
   */
  public function is_complete($user_id = null) {
    if(is_null($user_id)) {
      $user_id = get_current_user_id();
    }

    if(empty($user_id)) {
      return false;
    }

    $user_progress = models\UserProgress::find_one_by_user_and_lesson($user_id, $this->ID);

    return !empty($user_progress) && !empty($user_progress->id);
  }

  /**
   * Complete this lesson for the given user ID
   *
   * Creates a UserProgress record and fires hooks.
   *
   * @param int $user_id The user ID
   * @return void
   */
  public function complete($user_id) {
    $section_id = 0;
    $course_id = 0;

    if($section = $this->section()) {
      $section_id = $section->id;

      if($course = $section->course()) {
        $course_id = $course->ID;
      }
    }

    $has_started_course = models\UserProgress::has_started_course($user_id, $course_id);
    $has_started_section = models\UserProgress::has_started_section($user_id, $section_id);

    $user_progress = new models\UserProgress();
    $user_progress->lesson_id    = $this->ID;
    $user_progress->course_id    = $course_id;
    $user_progress->user_id      = $user_id;
    $user_progress->created_at   = lib\Utils::ts_to_mysql_date(time());
    $user_progress->completed_at = lib\Utils::ts_to_mysql_date(time());
    $user_progress->store();

    $user = new \MeprUser(get_current_user_id());
    if('memberpress\courses\models\Lesson' === get_class($this)){
      if(models\UserProgress::has_completed_lesson($user_id, $user_progress->lesson_id)){
        \MeprEvent::record('mpca-lesson-completed', $user, array(
          'lesson_id' => $user_progress->lesson_id
        ));
      }
    }

    do_action(base\SLUG_KEY . '_completed_lesson', $user_progress);

    if(false == $has_started_course) {
      do_action(base\SLUG_KEY . '_started_course', $user_progress);
    }

    if(false == $has_started_section) {
      do_action(base\SLUG_KEY . '_started_section', $user_progress, $section_id);
    }

    if(models\UserProgress::has_completed_course($user_id, $course_id)) {
      \MeprEvent::record('mpca-course-completed', $user, array(
        'course_id' => $user_progress->course_id
      ));
      do_action(base\SLUG_KEY . '_completed_course', $user_progress);
    }

    if(models\UserProgress::has_completed_section($user_id, $section_id)) {
      do_action(base\SLUG_KEY . '_completed_section', $user_progress);
    }
  }

  public static function get_thumbnail( $post ){
    $thumbnail_url = "";

    if( helpers\Lessons::is_a_lesson($post) ){
      if( has_post_thumbnail($post) ){
        $thumbnail_url = get_the_post_thumbnail_url($post);
      } else {
        $lesson = new models\Lesson($post->ID);
        $course = $lesson->course();
        $thumbnail_url = get_the_post_thumbnail_url($course->ID);
      }
    }
    elseif(helpers\Courses::is_a_course($post) && has_post_thumbnail($post)){
      $thumbnail_url = get_the_post_thumbnail_url($post);
    }

    return $thumbnail_url;
  }
}
