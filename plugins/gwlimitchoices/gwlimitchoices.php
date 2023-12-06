<?php
/**
 * Plugin Name: GP Limit Choices
 * Description: Limit how many times a choice may be selected for Radio Button, Drop Down and Checkbox fields.
 * Plugin URI: https://gravitywiz.com/documentation/gravity-forms-limit-choices/
 * Version: 1.7.13
 * Author: Gravity Wiz
 * Author URI: http://gravitywiz.com/
 * License: GPL2
 * Text Domain: gp-limit-choices
 * Domain Path: /languages
 * Perk: True
 * Update URI: https://gravitywiz.com/updates/gwlimitchoices
 */

define( 'GP_LIMIT_CHOICES_VERSION', '1.7.13' );

require 'includes/class-gp-bootstrap.php';

$gp_limit_choices_bootstrap = new GP_Bootstrap( 'class-gp-limit-choices.php', __FILE__ );
