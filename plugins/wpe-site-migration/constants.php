<?php

$slug = defined('WPMDB_CORE_SLUG') ? WPMDB_CORE_SLUG : 'wpmdb';

switch ($slug) {
    case 'wpe-site-migration':
        $prefix = 'wpesm_';
        break;
    default:
        $prefix = 'wpmdb_';
        break;
}

if ( ! defined('WPMDB_OPTION_PREFIX')) {
    define('WPMDB_OPTION_PREFIX', $prefix);
}

$options_constants = [
    'WPMDB_ERROR_LOG_OPTION'                      => 'error_log',
    'WPMDB_SETTINGS_OPTION'                       => 'settings',
    'WPMDB_SCHEMA_VERSION_OPTION'                 => 'schema_version',
    'WPMDB_SAVED_PROFILES_OPTION'                 => 'saved_profiles',
    'WPMDB_RECENT_MIGRATIONS_OPTION'              => 'recent_migrations',
    'WPMDB_REMOTE_MIGRATION_STATE_OPTION'         => 'remote_migration_state',
    'WPMDB_MIGRATION_STATE_OPTION'                => 'migration_state',
    'WPMDB_MIGRATION_OPTIONS_OPTION'              => 'migration_options',
    'WPMDB_REMOTE_RESPONSE_OPTION'                => 'remote_response',
    'WPMDB_USAGE_OPTION'                          => 'usage',
    'WPMDB_QUEUE_STATUS_OPTION'                   => 'queue_status',
    'WPMDB_FOLDER_TRANSFER_MEDIA_FILES_OPTION'    => 'folder_transfers_media_files_',
    'WPMDB_FOLDER_TRANSFER_THEME_FILES_OPTION'    => 'folder_transfers_themes_',
    'WPMDB_FOLDER_TRANSFER_PLUGIN_FILES_OPTION'   => 'folder_transfers_plugins_',
    'WPMDB_FOLDER_TRANSFER_MUPLUGIN_FILES_OPTION' => 'folder_transfers_muplugins_',
    'WPMDB_FOLDER_TRANSFER_OTHER_FILES_OPTION'    => 'folder_transfers_others_',
    'WPMDB_FOLDER_TRANSFER_CORE_FILES_OPTION'     => 'folder_transfers_core_',
    'WPMDB_UPDATE_DATA_OPTION'                    => 'upgrade_data',
    'WPMDB_DBRAINS_API_DOWN_OPTION'               => 'dbrains_api_down',
    'WPMDB_DISABLED_LEGACY_ADDONS_OPTION'         => 'disabled_legacy_addons',
    'WPMDB_TEMPORARILY_DISABLE_SSL_OPTION'        => 'temporarily_disable_ssl',
    'WPMDB_HELP_MESSAGE_OPTION'                   => 'help_message',
    'WPMDB_ADDONS_OPTION'                         => 'addons',
    'WPMDB_ADDON_SCHEMA_OPTION'                   => 'addon_schema',
    'WPMDB_ADDON_REQUIREMENT_CHECK_OPTION'        => 'addon_requirement_check',
    'WPMDB_SITE_BASIC_AUTH_OPTION'                => 'site_basic_auth',
    'WPMDB_MIGRATION_ID_TRANSIENT'                => 'migration_id',
    'WPMDB_WPE_REMOTE_COOKIE_OPTION'              => 'wpe_remote_cookie',
    'WPMDB_FILE_CHUNK_OPTION_PREFIX'              => 'file_chunk_',
    'WPMDB_LICENSE_RESPONSE_TRANSIENT'            => 'licence_response',
    'WPMDB_AVAILABLE_ADDONS_TRANSIENT'            => 'available_addons',
    'WPMDB_AVAILABLE_ADDONS_PER_USER_TRANSIENT'   => 'available_addons_per_user_',
    'WPMDB_PRODUCT_INFO_RESPONSE_TRANSIENT'       => 'product_info_response',
    'WPMDB_LICENSE_KEY_USER_META'                 => 'licence_key',
    'WPMDB_MIGRATION_STATS_OPTION'                => 'migration_stats',
];

foreach ($options_constants as $constant => $option) {
    if ( ! defined($constant)) {
        define($constant, $prefix . $option);
    }
}
define( 'WPMDB_DEACTIVATED_NOTICE_ID_TRANSIENT','wpmdb_deactivated_notice_id');
define( 'WPMDB_LITE_DEACTIVATED_FOR_PRO_ID','1');
define( 'WPMDB_PRO_DEACTIVATED_FOR_LITE_ID','2');
define( 'WPMDB_DEACTIVATED_FOR_WPESM_ID','3');
define( 'WPESM_DEACTIVATED_FOR_WPMDB_ID','4');
