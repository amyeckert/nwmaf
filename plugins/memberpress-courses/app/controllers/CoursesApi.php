<?php
namespace memberpress\courses\controllers;

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses\lib as lib;
use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

class CoursesApi extends lib\BaseCtrl {
  public static $namespace_str = 'mpcs';
  public static $resource_name_str = 'courses';

  // Here initialize our namespace and resource name.
  public function __construct() {
    parent::__construct();
  }

  public function load_hooks() {
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  /**
   * Register the routes for the objects of the controller.
   */
  public function register_routes() {
    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/lessons', array(
      array(
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => array( $this, 'fetch_lessons' ),
        'permission_callback' => array( $this, 'fetch_lessons_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/lessons/(?P<id>[\d]+)', array(
      array(
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'duplicate_lesson' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/quizzes', array(
      array(
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => array( $this, 'fetch_quizzes' ),
        'permission_callback' => array( $this, 'fetch_lessons_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/quizzes/(?P<id>[\d]+)', array(
      array(
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'duplicate_quizzes' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/curriculum/(?P<id>[\d]+)', array(
      array(
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => array( $this, 'get_curriculum' ),
        'permission_callback' => array( $this, 'fetch_lessons_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/question/all', array(
      array(
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => array( $this, 'fetch_all_questions' ),
        'permission_callback' => array( $this, 'fetch_lessons_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/question/(?P<id>[\d]+)/duplicate/(?P<quiz_id>[\d]+)', array(
      array(
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'duplicate_question' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/reserveQuestionId/(?P<id>[\d]+)', array(
      array(
        'methods'             => \WP_REST_Server::READABLE,
        'callback'            => array( $this, 'reserve_questionId' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/releaseQuestion/(?P<id>[\d]+)', array(
      array(
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'release_question' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
      ),
    ) );

    register_rest_route( self::$namespace_str, '/' . self::$resource_name_str .'/quiz/(?P<quiz_id>[\d]+)/questions', array(
      array(
        'methods'             => \WP_REST_Server::CREATABLE,
        'callback'            => array( $this, 'save_questions' ),
        'permission_callback' => array( $this, 'create_item_permissions_check' ),
      ),
    ) );
  }

  /**
   * Get a collection of lesson
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response
   */
  public function fetch_lessons( $request ) {
    return self::fetch_items( $request, 'mpcs-lesson', 'lessons');
  }

  /**
   * Get a collection of quizzes
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response
   */
  public function fetch_quizzes( $request ) {
    return self::fetch_items( $request, 'mpcs-quiz', 'quizzes');
  }

  /**
   * Get a collection of items
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @param string $post_type
   * @param string $data_key
   * @return \WP_REST_Response
   */
  public function fetch_items( $request, $post_type, $data_key ) {
    $params = $request->get_params();

    $args = [
      'post_type' => $post_type,
      'fields' => 'ids',
      's' => isset($params['s']) && is_string($params['s']) ? sanitize_text_field($params['s']) : '',
      'paged' => isset($params['paged']) && is_numeric($params['paged']) ? max(1, (int) $params['paged']) : 1,
      'post_status' => isset($params['post_status']) && is_array($params['post_status']) ? array_map('sanitize_key', $params['post_status']) : ['publish', 'draft', 'future'],
    ];

    $query = new \WP_Query($args);
    $lesson_ids = $query->get_posts();
    $data = [];

    foreach($lesson_ids as $lesson_id) {
      if($post_type == models\Quiz::$cpt) {
        $lesson = new models\Quiz($lesson_id);
      }
      else {
        $lesson = new models\Lesson($lesson_id);
      }

      $data[$data_key][] = $this->prepare_item_for_response($lesson);
    }

    $data['meta']['total'] = $query->found_posts;
    $data['meta']['max'] = $query->max_num_pages;
    $data['meta']['count'] = $query->post_count;

    return new \WP_REST_Response( $data, 200 );
  }

  /**
   * Check if a given request has access to get items
   *
   * @return bool
   */
  public function fetch_lessons_permissions_check() {
    return current_user_can( 'read' );
  }

  /**
   * Duplicate a lesson
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response|\WP_Error
   */
  public function duplicate_lesson( $request ) {
    $post_id = absint( $request->get_param( 'id' ) );
    $post = get_post( $post_id );

    if(!$post instanceof \WP_Post || !in_array($post->post_type, [models\Lesson::$cpt, models\Quiz::$cpt], true)) {
      return new \WP_Error('not-found', __('Post not found', 'memberpress-courses'), ['status' => 404]);
    }

    // args for new post
    $args = array(
      'comment_status' => $post->comment_status,
      'ping_status'    => $post->ping_status,
      'post_author'    => $post->post_author,
      'post_content'   => $post->post_type == models\Quiz::$cpt ? '' : $post->post_content, // Saving empty content initially for quizzes (see below)
      'post_excerpt'   => $post->post_excerpt,
      'post_name'      => $post->post_name,
      'post_parent'    => $post->post_parent,
      'post_password'  => $post->post_password,
      'post_status'    => $post->post_status,
      'post_title'     => $post->post_title,
      'post_type'      => $post->post_type,
      'to_ping'        => $post->to_ping,
      'menu_order'     => $post->menu_order
    );

    // insert the new post
    $new_post_id = wp_insert_post( $args );

    if(empty($new_post_id)) {
      return new \WP_Error('cant-create', __('Could not create duplicate post', 'memberpress-courses'), ['status' => 500]);
    }

    // add taxonomy terms to the new post
    $taxonomies = get_object_taxonomies( $post->post_type );
    foreach ( $taxonomies as $taxonomy ) {
      $post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
      wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
    }

    if($post->post_type == models\Quiz::$cpt) {
      // Duplicate the questions within the quiz content first, then update the post with the new content.
      // If we save the duplicated post content during wp_insert_post above, it triggers Question::sync_database which
      // detaches the questions from the original post.
      $quiz = new models\Quiz($new_post_id);
      $quiz->post_content = helpers\Questions::duplicate_quiz_questions($post->post_content, $new_post_id);
      $quiz->store();

      return new \WP_REST_Response($quiz->rec);
    }

    $lesson = new models\Lesson($new_post_id);

    return new \WP_REST_Response($lesson->rec);
  }

  /**
   * Fetches updated curriculum
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response
   */
  public function get_curriculum($request){
    $post_id = absint( $request->get_param( 'id' ) );
    $curriculum = helpers\Courses::course_curriculum($post_id);
    return new \WP_REST_Response( $curriculum, 200 );
  }

  /**
   * Fetches all questions from custom questions table
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response
   */
  public function fetch_all_questions($request) {
    $params = $request->get_params();
    $quiz_id = isset($params['id']) ? absint( $params['id'] ) : 0; //If no id provided, use 0

    $search = $params['search'] ? sanitize_text_field($params['search']) : '';
    $page = $params['page'] ? max(1, (int) $params['page']) : 1;

    $data = helpers\Questions::questions_with_meta($quiz_id, $search, $page);

    return new \WP_REST_Response( $data, 200 );
  }

  /**
   * Duplicate a question
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response|\WP_Error
   */
  public function duplicate_question(\WP_REST_Request $request) {
    $question_id = (int) $request->get_param('id');
    $quiz_id = (int) $request->get_param('quiz_id');

    $original = models\Question::find($question_id);

    if(!$original instanceof models\Question) {
      return new \WP_Error('not-found', __('Question not found', 'memberpress-courses'), ['status' => 404]);
    }

    $question = new models\Question();
    $question->load_from_array($original->get_values());
    $question->id = 0;
    $question->quiz_id = $quiz_id;
    $question->store();

    return new \WP_REST_Response(helpers\Questions::prepare_question($question));
  }

  /**
   * Reserves a row in the mepr_questions table for a question block
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response
   */
  public function reserve_questionId($request) {
    $quiz_id = absint( $request->get_param( 'id' ) );
    $id = helpers\Questions::save_question_placeholder($quiz_id);

    return new \WP_REST_Response( $id, 200 );
  }

  /**
   * Releases a reserved id if no data was saved in it.
   * OR
   * If data was saved into the question, sets the quiz_id
   * to 0 to remove it from the quiz. Essentiall the question will be
   * orphaned.
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response
   */
  public function release_question($request) {
    $id = absint( $request->get_param( 'id' ) );
    helpers\Questions::maybe_orphan_or_delete_question($id);

    return new \WP_REST_Response( $id, 200);
  }

  /**
   * Save the questions for a quiz
   *
   * @param \WP_REST_Request $request Full data about the request.
   * @return \WP_REST_Response|\WP_Error
   */
  public function save_questions($request) {
    $quiz_id = (int) $request->get_param('quiz_id');
    $questions = $request->get_param('questions');
    $order = $request->get_param('order');

    $quiz = models\Quiz::find($quiz_id);

    if(!$quiz instanceof models\Quiz) {
      return new \WP_Error('not-found', __('Quiz not found', 'memberpress-courses'), ['status' => 404]);
    }

    if(!is_array($questions) || !is_array($order)) {
      return new \WP_Error('bad-request', __('Bad request', 'memberpress-courses'), ['status' => 400]);
    }

    $question_ids = $quiz->get_questions(true);
    $errors = [];
    $replaced_ids = [];

    foreach($questions as $id => $question) {
      if(!is_numeric($id)) { // Skip placeholders
        continue;
      }

      $index = array_search($id, $order);
      $question['number'] = $index !== false ? $index + 1 : 1;
      $result = helpers\Questions::save_question($quiz->ID, $question);

      if($result instanceof \WP_Error) {
        $errors[] = ['id' => $id, 'message' => $result->get_error_message()];
      }
      elseif(is_numeric($result) && $result != $id) {
        // The question ID changed when it was saved, which could happen if the question was deleted
        // beforehand, we need to return the new ID so that the question store can be updated.
        $replaced_ids[] = ['oldId' => $id, 'newId' => $result];

        // Replace the old ID with the new ID within the question IDs from the post content
        if(($key = array_search($id, $question_ids)) !== false) {
          $question_ids[$key] = $result;
        }
      }
    }

    if(!empty($question_ids)) {
      models\Question::sync_database($quiz->ID, $question_ids);
    }

    return new \WP_REST_Response([
      'errors' => $errors,
      'ids' => $replaced_ids,
    ]);
  }

  /**
   * Check if a given request has access to create items
   *
   * @return bool
   */
  public function create_item_permissions_check() {
    return current_user_can( 'edit_pages' );
  }

  /**
   * Prepare the item for the REST response
   *
   * @param models\Lesson $lesson
   * @return array
   */
  public function prepare_item_for_response($lesson) {
    $course = $lesson->course();

    return [
      'ID' => $lesson->ID,
      'title' => $lesson->post_title,
      'permalink' => get_permalink($lesson->ID),
      'type' => $lesson->post_type,
      'courseID' => $course ? $course->ID : '',
      'courseTitle' => $course ? $course->post_title : '',
    ];
  }
}
