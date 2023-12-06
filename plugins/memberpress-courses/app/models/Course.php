<?php
namespace memberpress\courses\models;

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses\lib as lib;
use memberpress\courses\models as models;

/**
 * @property string $status The course status, 'enabled' or 'disabled'
 * @property string $page_template The page template
 * @property int $menu_order The menu order
 * @property string $sales_url The sales URL
 * @property string $require_previous Require the previous lesson/quiz, 'enabled' or 'disabled'
 * @property string $show_results Show the quiz results, 'enabled' or 'disabled'
 * @property string $show_answers Show the quiz answers, 'enabled' or 'disabled'
 * @property string $accordion_course Collapsible course menu, 'enabled' or 'disabled'
 * @property string $accordion_sidebar Collapsible course menu in sidebar, 'enabled' or 'disabled'
 * @property string $certificates_enable
 * @property string $certificates_force_download_pdf
 * @property string $certificates_paper_size
 * @property string $certificates_logo
 * @property string $certificates_instructor_signature
 * @property string $certificates_instructor_name
 * @property string $certificates_instructor_title
 * @property string $certificates_bottom_logo
 * @property string $certificates_signature
 * @property string $certificates_text_color
 * @property string $certificates_expiration_date
 * @property string $certificates_completion_date
 * @property string $certificates_share_link
 * @property string $certificates_expires_value
 * @property string $certificates_expires_unit
 * @property string $certificates_expires_reset
 * @property string $certificates_background_color
 * @property string $certificates_title
 * @property string $certificates_footer_message
 * @property string $certificates_style
 */
class Course extends lib\BaseCptModel {
  public static $cpt = 'mpcs-course';
  public static $nonce_str = 'mpcs-course-nonce';
  public static $page_template_str = 'mpcs-course-page-template';
  public static $page_status_str = 'mpcs-course-page-status';
  public static $lesson_title_str = 'mpcs-course-page-lesson-title';
  public static $sales_url_str = 'mpcs-sales-url';
  public static $require_previous_str = 'mpcs-require-previous';
  public static $show_results_str = 'mpcs-show-results';
  public static $show_answers_str = 'mpcs-show-answers';
  public static $accordion_course_str = 'mpcs-accordion-course';
  public static $accordion_sidebar_str = 'mpcs-accordion-sidebar';
  public static $certificates_enable_str = 'mpcs-certificates-enable';
  public static $certificates_force_download_pdf_str = 'mpcs-certificates-force-download-pdf';
  public static $certificates_paper_size_str = 'mpcs-certificates-paper-size';
  public static $certificates_logo_str = 'mpcs-certificates-logo';
  public static $certificates_bottom_logo_str = 'mpcs-certificates-bottom-logo';
  public static $certificates_signature_str = 'mpcs-certificates-signature';
  public static $certificates_instructor_signature_str = 'mpcs-certificates-instructor-signature';
  public static $certificates_instructor_name_str = 'mpcs-certificates-instructor-name';
  public static $certificates_instructor_title_str = 'mpcs-certificates-instructor-title';
  public static $certificates_text_color_str = 'mpcs-certificates-text-color';
  public static $certificates_background_color_str = 'mpcs-certificates-background-color';
  public static $certificates_title_str = 'mpcs-certificates-title';
  public static $certificates_footer_message_str = 'mpcs-certificates-footer-message';
  public static $certificates_style_str = 'mpcs-certificates-style';
  public static $certificates_expiration_date_str = 'mpcs-certificates-expiration-date';
  public static $certificates_completion_date_str = 'mpcs-certificates-completion-date';
  public static $certificates_share_link_str = 'mpcs-certificates-share-link';
  public static $certificates_expires_unit_str = 'mpcs-certificates-expires-unit';
  public static $certificates_expires_value_str = 'mpcs-certificates-expires-value';
  public static $certificates_expires_reset_str = 'mpcs-certificates-expires-reset';
  public static $permalink_slug = 'courses';
  public $statuses;

  public function __construct($obj = null) {
    parent::__construct($obj);
    $this->load_cpt(
      $obj,
      self::$cpt,
      array(
        'status'        => array('default' => 'enabled', 'type' => 'string'),
        'page_template' => array('default' => null, 'type' => 'string'),
        'menu_order'    => array('default' => 0, 'type' => 'int'),
        'sales_url'    => array('default' => '', 'type' => 'string'),
        'require_previous' => array('default' => 'disabled', 'type' => 'string'),
        'show_results' => array('default' => 'disabled', 'type' => 'string'),
        'show_answers' => array('default' => 'disabled', 'type' => 'string'),
        'certificates_bottom_logo' => array('default' => '', 'type' => 'string'),
        'certificates_instructor_signature' => array('default' => '', 'type' => 'string'),
        'certificates_instructor_name' => array('default' => 'John Smith', 'type' => 'string'),
        'certificates_instructor_title' => array('default' => 'Director of Something', 'type' => 'string'),
        'certificates_enable' => array('default' => 'disabled', 'type' => 'string'),
        'certificates_force_download_pdf' => array('default' => 'enabled', 'type' => 'string'),
        'certificates_paper_size' => array('default' => 'letter', 'type' => 'string'),
        'certificates_logo' => array('default' => '', 'type' => 'string'),
        'certificates_signature' => array('default' => '', 'type' => 'string'),
        'certificates_text_color' => array('default' => '#3c3c3c', 'type' => 'string'),
        'certificates_background_color' => array('default' => '', 'type' => 'string'),
        'certificates_title' => array('default' => 'This certificate is awarded to', 'type' => 'string'),
        'certificates_footer_message' => array('default' => 'Has successfully completed this course', 'type' => 'string'),
        'certificates_style' => array('default' => 'style_a', 'type' => 'string'),
        'certificates_expiration_date' => array('default' => 'disabled', 'type' => 'string'),
        'certificates_completion_date' => array('default' => 'disabled', 'type' => 'string'),
        'certificates_share_link' => array('default' => 'disabled', 'type' => 'string'),
        'certificates_expires_unit' => array('default' => 'day', 'type' => 'string'),
        'certificates_expires_value' => array('default' => '1', 'type' => 'string'),
        'certificates_expires_reset' => array('default' => 'disabled', 'type' => 'string'),
        'accordion_course' => array( 'default' => 'enabled', 'type' => 'string' ),
        'accordion_sidebar' => array('default' => 'enabled', 'type' => 'string'),
        'lesson_title' => array( 'default' => 'enabled', 'type' => 'string' ),
      )
    );

    $this->statuses = array(
      'enabled',
      'disabled'
    );
  }

  /**
   * Validate this course
   *
   * @throws lib\ValidationException On validation failure
   */
  public function validate() {
    lib\Validate::is_in_array($this->status, $this->statuses, 'status');
    lib\Validate::is_in_array($this->require_previous, $this->statuses, 'require_previous');
    lib\Validate::is_in_array($this->show_results, $this->statuses, 'show_results');
    lib\Validate::is_in_array($this->show_answers, $this->statuses, 'show_answers');
  }

  public function sanitize() {
    // $this->first_name = sanitize_text_field($this->first_name);
  }

  /**
   * Get all the sections of this course
   *
   * @return Section[]
   */
  public function sections() {
    return models\Section::find_all_by_course($this->ID);
  }

  /**
  * Get all the lessons for this course, ordered by section then by lesson
   *
  * @return array<Lesson|Quiz>|int[]
  */
  public function lessons($type='objects') {
    $lessons = array();
    $sections = models\Section::find_all_by_course($this->ID);

    foreach($sections as $section) {
      $lessons = array_merge(
        $lessons,
        models\Lesson::find_all_by_section($section->id)
      );
    }

    if($type=='ids') {
      return array_map( function($lesson) {
          return $lesson->ID;
        },
        $lessons
      );
    }
    else {
      return $lessons;
    }
  }

  /**
   * Find memberships containing course
   *
   * @return \MeprProduct[]
   */
  public function memberships() {
    $memberships = array();
    $course_post = get_post($this->ID);
    $access_list = \MeprRule::get_access_list($course_post);

    foreach ($access_list['membership'] as $membership_id) {
      $memberships[] = new \MeprProduct($membership_id);
    }

    return $memberships;
  }

  /**
   * Get the number of lessons for this course
   *
   * @return int Query count result
   */
  public function number_of_lessons() {
    global $wpdb;
    $db = new lib\Db;
    $section_id_str = models\Lesson::$section_id_str;
    $post_types = Lesson::lesson_cpts();

    $query = $wpdb->prepare("
      SELECT COUNT(*)
        FROM {$wpdb->posts}
        JOIN {$wpdb->postmeta} AS pm ON pm.post_id = {$wpdb->posts}.ID AND pm.meta_key = '{$section_id_str}'
        JOIN {$db->sections} ON {$db->sections}.id = pm.meta_value
       WHERE post_type IN ('" . implode("', '", array_map('esc_sql', $post_types)) . "')
         AND post_status = 'publish'
         AND {$db->sections}.course_id = %d",
      $this->ID
    );

    $count = $wpdb->get_var($query);

    return (int) $count;
  }

  public function maybe_reset_progress($user_id)
  {
    // @todo
  }

  /**
   * Return the user's progress for this course
   *
   * @param int $user_id User to check progress against
   * @return string Percentage of completed / total lessons for this course
   */
  public function user_progress($user_id) {
    global $wpdb;
    // reset if needed
    if (!empty($this->ID)) {
      if ( $this->certificates_expires_reset == 'enabled' ) {
        $last_completion_date = models\UserProgress::get_course_completion_date( $user_id, $this->ID );
        $expires_value        = $this->certificates_expires_value;
        $expires_unit         = $this->certificates_expires_unit;

        switch ( $expires_unit ) {
          case 'day':
            $period = $expires_value . "D";
            break;
          case 'week':
            $period = $expires_value * 7 . 'D';
            break;
          case 'month':
            $period = $expires_value . "M";
            break;
          case 'year':
            $period = $expires_value . "Y";
            break;
          default:
            $period = $expires_value . "Y";
        }

        $last_completion_datetime = new \DateTime( $last_completion_date );
        $last_completion_datetime->add( new \DateInterval( 'P' . $period ) );

        if ( $last_completion_datetime->getTimestamp() < time() ) {
          models\UserProgress::reset_course_progress( $user_id, $this->ID );
        }
      }
    }

    if(UserProgress::has_completed_course($user_id, $this->ID)) {
      return 100.00;
    }

    $total_lessons = $this->number_of_lessons();

    // We don't need to go further
    if($total_lessons === 0) {
      return 0;
    }

    $db = new lib\Db;

    $lesson_ids = $this->lessons('ids');
    $lesson_ids_str = $db->prepare_array('%d', $lesson_ids);

    $total_progress = (float)($total_lessons * 100.00);

    $q = $wpdb->prepare("
        SELECT SUM(up.progress)
          FROM {$db->user_progress} AS up
         WHERE up.course_id = %d
           AND up.lesson_id IN (" . $lesson_ids_str . ")
           AND up.user_id = %d
      ",
      $this->ID,
      $user_id
    );

    $completed_progress = (float)$wpdb->get_var($q);

    $progress = (float)($completed_progress / $total_progress * 100.00);

    return number_format(min($progress,100));
  }

  /**
   * Remove an existing section not in $sections array
   *
   * @param array $sections Sections from form
   */
  public function remove_sections($sections = array()) {
    $existing_sections = $this->sections();

    // Remove sections that were removed in the UI
    foreach ($existing_sections as $section) {
      if(!isset($sections[$section->uuid])) {
        $section->destroy();
      }
    }
  }

  /**
   * Get all authors of at least one course
   *
   * @return array
   */
  public static function post_authors() {
    global $wpdb;

    $q = $wpdb->prepare("
        SELECT usr.ID, usr.display_name, usr.user_login
          FROM {$wpdb->prefix}users AS usr
          INNER JOIN {$wpdb->prefix}posts AS pst
          ON usr.ID = pst.post_author
         WHERE pst.post_type = %s
         GROUP BY usr.ID
      ",
      models\Course::$cpt
    );

    return $wpdb->get_results($q);
  }

  /**
  * Get all the quizzes for this course, ordered by section then by lesson
   *
  * @return array<Quiz>|int[]
  */
  public function quizzes($type='objects') {
    $quizzes = array();
    $sections = models\Section::find_all_by_course($this->ID);

    foreach($sections as $section) {
      $quizzes = array_merge(
        $quizzes,
        models\Lesson::find_all_by_section($section->id, array(Quiz::$cpt))
      );
    }

    if($type=='ids') {
      return array_map( function($quiz) {
          return $quiz->ID;
        },
        $quizzes
      );
    }
    else {
      return $quizzes;
    }
  }
}
