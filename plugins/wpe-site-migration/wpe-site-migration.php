<?php
/*
Plugin Name: WP Engine Site Migration
Description: Migrate any WordPress site to WP Engine or Flywheel. Copy all database tables and site files or customize the migration to include only what you need.
Author: WP Engine
Version: 1.0.0-beta.15
Author URI: https://wpengine.com/?utm_source=wpesm_plugin&utm_medium=referral&utm_campaign=bx_prod_referral&utm_content=wpesm_plugin_author_link
Network: True
Text Domain: wp-migrate-db
Domain Path: /languages/
*/

// Copyright (c) 2023 WP Engine. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

defined( 'ABSPATH' ) || exit;

$wpmdb_base_path = dirname( __FILE__ );
$wpmdb_base_file = __FILE__;

require_once 'version-wpe.php';

if ( ! defined('WPMDB_CORE_SLUG')) {
    define('WPMDB_CORE_SLUG', 'wpe-site-migration');
}

define('WPE_MIGRATIONS', true);
define('WPMDB_PLUGIN_TITLE', 'WP Engine Site Migration');
define("WPMDB_PLUGIN_URL", plugin_dir_url(__FILE__));

//Setup constants
require_once 'constants.php';

$plugin_root = '/';

if(!defined('WPMDB_VENDOR_DIR')){
    define('WPMDB_VENDOR_DIR', __DIR__ . $plugin_root."vendor");
}

require WPMDB_VENDOR_DIR . '/autoload.php';

require 'setup-plugin.php';

if ( version_compare( PHP_VERSION, WPMDB_MINIMUM_PHP_VERSION, '>=' ) ) {
	require_once $wpmdb_base_path . '/class/autoload.php';
	require_once $wpmdb_base_path . '/setup-wpe.php';
}

/**
 * Called when the plugin is deactivated
 *
 **/
function wpe_site_migration_deactivate_plugin() {
	// Remove the compatibility plugin when the plugin is deactivated
	do_action( 'wp_migrate_db_remove_compatibility_plugin' );
	//Remove migration related options
	do_action( 'wpmdb_deactivate_plugin' );
}

/**
 * Called when the plugin is deleted
 *
 **/
function wpe_site_migration_delete_plugin()
{
    \DeliciousBrains\WPMDB\SiteMigration\Plugin\Scrubber::delete_all_plugin_options();
    \DeliciousBrains\WPMDB\SiteMigration\Plugin\Scrubber::delete_usermeta();
}
