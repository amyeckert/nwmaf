<?php

defined('ABSPATH') || exit;

use DeliciousBrains\WPMDB\Common\Util\Util;

/**
 * Populate the $wpe_migrations global with an instance of the WPMDBPro class and return it.
 *
 * @return DeliciousBrains\WPMDB\WPMigrateDBPro The one true global instance of the WPMDBPro class.
 */
function wpe_migrations()
{
	global $wpe_migrations;

	//Load in front-end code
	require_once __DIR__ . '/react-wp-scripts.php';

	if (!is_null($wpe_migrations)) {
		return $wpe_migrations;
	}

	$wpe_migrations = new DeliciousBrains\WPMDB\SiteMigration\WPESiteMigration();
	$wpe_migrations->register();

	return $wpe_migrations;
}

/**
 * WPE Migrations Loaded hooked to 'plugins_loaded'
 *
 **/
function wpe_migrations_loaded()
{
	if (Util::is_frontend()) {
		return false;
	}

	// @TODO revisit since we're reming is_admin()
	// exit quickly unless: standalone admin; one of our AJAX calls
	if (is_multisite() && !current_user_can('manage_network_options') && !Util::wpmdb_is_ajax()) {
		return false;
	}
	if (function_exists('wpe_migrations')) {
		register_deactivation_hook(dirname(__FILE__) . '/wpe-site-migration.php', 'wpe_site_migration_deactivate_plugin');
		register_uninstall_hook(dirname(__FILE__) . '/wpe-site-migration.php', 'wpe_site_migration_delete_plugin');
		wpe_migrations();
	}
}

add_action('plugins_loaded', 'wpe_migrations_loaded');

/**
 * Checks if another build of WP Migrate is active and deactivates it.
 * Deactivates this pluugin when activating another build of WP Migrate
 * To be hooked on `activated_plugin` so other plugin is deactivated when current plugin is activated.
 * @handles activated_plugin
 * @param string $plugin
 *
 */
function wpe_deactivate_other_instances($plugin)
{
	$basename = basename($plugin);
	$migrate_plugins = ['wp-migrate-db-pro.php', 'wp-migrate-db.php', 'wpe-site-migration.php'];
	if (!in_array($basename, $migrate_plugins)) {
		return;
	}

	if ('wpe-site-migration.php' === $basename) {
		$plugins_to_handle = ['wp-migrate-db-pro.php', 'wp-migrate-db.php'];
		$deactivated_notice_id = WPMDB_DEACTIVATED_FOR_WPESM_ID;
	} else {
		$plugins_to_handle = ['wpe-site-migration.php'];
		$deactivated_notice_id = WPESM_DEACTIVATED_FOR_WPMDB_ID;
	}

	$active_plugins = wpmdb_get_active_plugins();

	foreach ($active_plugins as $active_plugin) {
		foreach ($plugins_to_handle as $plugin_to_handle) {
			if (false !== strpos($active_plugin, $plugin_to_handle)) {
				set_transient(WPMDB_DEACTIVATED_NOTICE_ID_TRANSIENT, $deactivated_notice_id, HOUR_IN_SECONDS);
				deactivate_plugins($active_plugin);

				return;
			}
		}
	}
}

add_action('activated_plugin', 'wpe_deactivate_other_instances');
