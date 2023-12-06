<?php
namespace memberpress\courses\models;

if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

use memberpress\courses\lib as lib;
use memberpress\courses\models as models;

/**
 * @property int $id The section ID
 * @property string $title The title
 * @property string $description The description
 * @property int $course_id The course ID for this section
 * @property int $section_order The section order index
 * @property string $created_at Datetime in MySQL format
 * @property string $uuid The section UUID
 */
class Section extends lib\BaseModel {
  public function __construct($obj = null) {
    $this->initialize(
      array(
        'id' =>            array('default' => 0,    'type' => 'integer'),
        'title' =>         array('default' => '',   'type' => 'string'),
        'description' =>   array('default' => '',   'type' => 'string'),
        'course_id' =>     array('default' => 0,    'type' => 'integer'),
        'section_order' => array('default' => 0,    'type' => 'integer'),
        'created_at' =>    array('default' => null, 'type' => 'datetime'),
        'uuid' =>          array('default' => null, 'type' => 'string')
      ),
      $obj
    );
  }

  /**
   * Used to validate the section object
   *
   * @throws lib\ValidationException On validation failure
   */
  public function validate() {
    lib\Validate::not_empty($this->title, 'title');
    lib\Validate::not_empty($this->uuid, 'uuid');
  }

  /**
   * Used to create or update the section record
   *
   * @param bool $validate Validate before storing, default true
   * @return int|\WP_Error Section ID, or WP_Error on validation error
   */
  public function store($validate = true) {
    if($validate) {
      try {
        $this->validate();
      }
      catch(lib\ValidationException $e) {
        return new \WP_Error(get_class($e), $e->getMessage());
      }
    }

    // Avoid duplicate sections in the database
    if( isset($this->uuid) && empty($this->id) ){
      $db = new lib\Db;
      $section = $db->get_one_record($db->sections, array('uuid' => $this->uuid));

      if(\is_object($section) && isset($section->id)){
        $this->id = $section->id;
      }
    }

    if(isset($this->id) && (int) $this->id > 0) {
      $this->update();
    }
    else {
      $this->id = self::create($this);
    }

    return $this->id;
  }

  /**
  * Destroy the section
   *
  * @return int|false Returns number of rows affected or false
  */
  public function destroy() {
    $db = new lib\Db;

    // First let's clean up the lessons
    $lessons = $this->lessons();
    foreach ($lessons as $lesson) {
      $lesson->remove_from_section();
    }

    return $db->delete_records($db->sections, array('id' => $this->id));
  }

  /**
   * Fetch lessons for section
   *
   * @return Lesson[] An array of Lesson objects
   */
  public function lessons($include_private=true) {
    return models\Lesson::find_all_by_section($this->id,null,$include_private);
  }

  /**
   * Used to create the section record
   *
   * @param Section $section
   * @return int The section ID
   */
  public static function create($section) {
    $db = new lib\Db;
    $attrs = $section->get_values();

    return $db->create_record($db->sections, $attrs);
  }

  /**
   * Used to update the section record
   *
   * @return int The section ID
   */
  private function update() {
    $db = new lib\Db;
    $attrs = $this->get_values();
    return $db->update_record($db->sections, $this->id, $attrs);
  }

  public static function find_all() {
    $db = new lib\Db;

    $records = $db->get_records($db->sections);

    $sections = array();
    foreach($records as $rec) {
      $sections[$rec->id] = $rec->title;
    }

    return $sections;
  }

  /**
   * Find all by course
   *
   * @param integer $course_id
   * @return Section[] Array of Section objects ordered by section_order
   */
  public static function find_all_by_course($course_id) {
    $db = new lib\Db;

    $records = $db->get_records($db->sections, compact('course_id'), 'section_order');

    $sections = array();
    foreach($records as $rec) {
      $sections[] = new models\Section($rec->id);
    }

    return $sections;
  }

  /**
   * Find all by title
   *
   * @param string $title
   * @return Section[] Array of Section objects
   */
  public static function find_all_by_title($title) {
    $db = new lib\Db;

    $records = $db->get_records($db->sections, compact('title'), '', '', [], OBJECT, true);

    $sections = array();
    foreach($records as $rec) {
      $sections[] = new models\Section($rec->id);
    }

    return $sections;
  }

  /**
   * Find section by id
   *
   * @param integer $id
   * @return object Section object
   */
  public static function find_by_id($id) {
    $db = new lib\Db;

    return $db->get_one_record($db->sections, compact('id'));
  }

  /**
   * Get section data
   *
   * @param $id
   * @return object Section object
   */
  public static function get_section($id) {
    $section = self::find_by_id($id);

    $obj = new \stdClass();
    $course_title = get_the_title($section->course_id);
    $obj->id = $section->id;
    $obj->label = $section->title;
    $obj->slug = $course_title;
    $obj->desc = "ID: {$section->id} | Course: {$course_title}";

    return $obj;
  }

  /**
   * Get the course for this section
   *
   * @return Course|false
   */
  public function course() {
    return models\Course::find($this->course_id);
  }

  /**
   * Remove lessons from section
   *
   * @param array $section_lessons Lessons for section from the admin
   */
  public function remove_unassigned_lessons($section_lessons) {
    $existing_lessons = $this->lessons();

    foreach($existing_lessons as $lesson) {
      if(!\in_array($lesson->ID, $section_lessons)) {
        $lesson->remove_from_section();
      }
    }
  }
}
