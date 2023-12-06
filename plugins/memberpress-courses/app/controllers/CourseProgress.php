<?php
namespace memberpress\courses\controllers;

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses as base;
use memberpress\courses\lib as lib;
use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

class CourseProgress extends lib\BaseCtrl {
  public function load_hooks() {

    if( !defined('MPCA_EDITION') ){
      return;
    }

    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 100);
    add_action('mpca-sub-accounts-links', array($this, 'mpca_sub_account_links'), 20, 3);
    add_action('wp_footer', array($this, 'mpca_sub_account_wp_footer'), 20, 3);
    add_action('wp_ajax_nopriv_mpcs_ca_view_course_progress', array($this, 'mpcs_ca_view_course_progress'), 20, 3);
    add_action('wp_ajax_mpcs_ca_view_course_progress', array($this, 'mpcs_ca_view_course_progress'), 20, 3);
    if(isset($_GET['course'])) {
      add_filter('mepr-admin-members-cols', array($this, 'customize_admin_members_cols'));
      add_filter('mepr_members_list_table_row', array($this, 'customize_admin_members_table_content'), 10, 4);
    }
  }

  function mpca_sub_account_links( $mepr_current_user, $ca, $sa ){
    echo '<a href="" data-ca="'.$ca->id.'" data-sa="'.$sa->ID.'" class="mpca-course-sub-account-progress">'.__('Course Progress', 'memberpress-courses').'</a>';
  }

  function mpcs_ca_view_course_progress(){

    lib\Utils::check_ajax_referer('ca_course_progress', 'nonce');

    if( ! is_user_logged_in() ){
        esc_html_e('Your session has timedout. Please login.', 'memberpress-courses');
        exit;
    }

    if( ! class_exists('\MPCA_Corporate_Account') ){
        esc_html_e('Oops! Unable to complete the request.', 'memberpress-courses');
        exit;
    }

    $ca = isset($_POST['ca']) ? $_POST['ca'] : 0;
    $sa = isset($_POST['sa']) ? $_POST['sa'] : 0;

    $user_id = \get_current_user_id();
    $mpca = new \MPCA_Corporate_Account( (int) $ca );
    $user = new \MeprUser( (int) $sa) ;
    $mpca_id = \get_user_meta( (int) $sa, 'mpca_corporate_account_id', true );

    if($mpca->user_id == $user_id && $mpca_id == $ca ){  // check if ca and sa user is valid.
       $my_courses = array();

      $course_posts = \get_posts(array('post_type' => models\Course::$cpt, 'post_status' => 'publish', 'numberposts' => -1));
      foreach ($course_posts as $course) {
        if(!\MeprRule::is_locked_for_user($user, $course)) {
          $my_courses[] = new models\Course($course->ID);
        }
      }

      \MeprView::render('/account/ca/courses_progress', get_defined_vars());
    }else{
       esc_html_e('Invalid request.', 'memberpress-courses');
    }

    exit;
  }

  /**
   * Enqueue scripts
   *
   * @return void
   */
  public function enqueue_scripts() {
    if( isset($_GET['ca']) && isset($_GET['action']) && $_GET['action'] == 'manage_sub_accounts' ){

      \wp_enqueue_style('mpcs-progress', base\CSS_URL . '/progress.css', array(), base\VERSION);
      \wp_enqueue_style('ca-course-progress', base\CSS_URL . '/ca-course-progress.css', array(), base\VERSION);
      \wp_enqueue_script('ca-course-progress-js', base\JS_URL . '/ca-course-progress.js', array('jquery'), base\VERSION, true);
      \wp_localize_script( 'ca-course-progress-js', 'mpca_progress',
        array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce('ca_course_progress')
        )
      );
    }
  }

  /**
   * WP Fotoer
   *
   * @return void
   */
  public function mpca_sub_account_wp_footer() {
    if( isset($_GET['ca']) && isset($_GET['action']) && $_GET['action'] == 'manage_sub_accounts' ){
     echo'<div id="course-progress-modal" class="course-progress-modal"><div class="course-progress-modal-content"><span class="course-progress-close-button"><img src="'.base\IMAGES_URL.'/icon-close.png" alt="x"></span><span id="mpca-subaccount-progress"></div></div>';
    }
  }

  function customize_admin_members_cols($cols) {
    $cols['col_course_progress'] = __('Course Progress', 'memberpress-courses');

    return $cols;
  }

  public function customize_admin_members_table_content($attributes, $rec, $column_name, $column_display_name) {
    if($column_name === 'col_course_progress') {
      $course_id = isset($_GET['course']) ? sanitize_text_field($_GET['course']) : '';
      $course = new models\Course((int)$course_id);
      $user = get_user_by('login', $rec->username);
      $progress = $course->user_progress($user->ID);
      ?>
      <style>
        .course-progress {
          background-color: #ffffff; }

        .form-table .course-progress {
          width: 250px; }

        .course-progress .user-progress {
          text-align: center;
          white-space: nowrap;
          background-color: #4caf50;
          height: 100%;
          display: block;
          width: 0%; }
      </style>
      <td <?php echo $attributes; ?>>
        <div class="course-progress">
          <div class="user-progress" data-value="<?php echo $progress; ?>" style="width: <?php echo $progress; ?>%;"><?php echo $progress; ?>%</div>
        </div>
      </td>
      <?php
    }
  }
}
