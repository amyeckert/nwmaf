<?php
namespace memberpress\courses\lib;
use memberpress\courses\helpers as helpers;
use memberpress\courses\models as models;

if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); }

global $wp_rewrite;
$lessons = '/' . helpers\Courses::get_permalink_base() . '/%course_slug%/' . helpers\Lessons::get_permalink_base();
$quizzes = '/' . helpers\Courses::get_permalink_base() . '/%course_slug%/' . models\Quiz::$permalink_slug;
$wp_rewrite->add_rewrite_tag( "%course_slug%", '([^/]+)', "course=" );
$wp_rewrite->add_permastruct( helpers\Lessons::get_permalink_base(), $lessons, false );
$wp_rewrite->add_permastruct( models\Quiz::$permalink_slug, $quizzes, false );
delete_option( 'mepr_courses_flushed_rewrite_rules' );

// The very first time Courses is installed and activated, enable classroom mode
$options = \get_option('mpcs-options');
if(!$options){
  $options['classroom-mode'] = true;
  update_option('mpcs-options', $options);
}