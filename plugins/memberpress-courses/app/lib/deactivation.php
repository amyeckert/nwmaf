<?php
namespace memberpress\courses\lib;
use memberpress\courses\helpers as helpers;
use memberpress\courses\models as models;

if(!defined('ABSPATH')) { die('You are not allowed to call this page directly.'); }

global $wp_rewrite;
$wp_rewrite->add_permastruct( helpers\Lessons::get_permalink_base(), '' );
$wp_rewrite->add_permastruct( models\Quiz::$permalink_slug, '' );
flush_rewrite_rules();
