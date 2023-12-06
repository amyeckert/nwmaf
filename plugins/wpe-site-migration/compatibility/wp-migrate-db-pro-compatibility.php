<?php
/*
Plugin Name: WP Engine Site Migration Compatibility
Description: Prevents 3rd party plugins from being loaded during a migration specific operation
Author: WP Engine
Version: 1.4
Author URI: https://wpengine.com/?utm_source=migrate_plugin&utm_medium=referral&utm_campaign=bx_prod_referral&utm_content=migrate_mu_plugin_author_link
*/

defined('ABSPATH') || exit;

if ( ! version_compare(PHP_VERSION, '5.4', '>=') || ! empty($GLOBALS['wpmdb_compatibility']['active'])) {
    return;
}

if (defined('WP_PLUGIN_DIR')) {
    $plugins_dir = trailingslashit(WP_PLUGIN_DIR);
} elseif (defined('WPMU_PLUGIN_DIR')) {
    $plugins_dir = trailingslashit(WPMU_PLUGIN_DIR);
} elseif (defined('WP_CONTENT_DIR')) {
    $plugins_dir = trailingslashit(WP_CONTENT_DIR) . 'plugins/';
} else {
    $plugins_dir = plugin_dir_path(__FILE__) . '../plugins/';
}

$compat_class_path            = 'class/Common/Compatibility/Compatibility.php';
$compat_class_name            = 'DeliciousBrains\WPMDB\Common\Compatibility\Compatibility';
$wpmdbpro_compatibility_class = $plugins_dir . 'wp-migrate-db-pro/' . $compat_class_path;
$wpmdb_compatibility_class    = $plugins_dir . 'wp-migrate-db/' . $compat_class_path;
$wpesm_compatibility_class    = $plugins_dir . 'wpe-site-migration/' . $compat_class_path;

// As none of the needed plugins should have been loaded yet,
// and we can't test whether any of them are active without affecting the
// mechanism we later use to "unload" plugins during a migration specific
// request, we can only test for and load the compatibility code in an order
// that is most likely to give us the most up-to-date or relevant code.
if (file_exists($wpesm_compatibility_class)) {
    include_once $wpesm_compatibility_class;
} elseif (file_exists($wpmdbpro_compatibility_class)) {
    include_once $wpmdbpro_compatibility_class;
} elseif (file_exists($wpmdb_compatibility_class)) {
    include_once $wpmdb_compatibility_class;
}

if (class_exists($compat_class_name)) {
    $compatibility = new $compat_class_name;
    $compatibility->register();
    $GLOBALS['wpmdb_compatibility']['active'] = true;
}
