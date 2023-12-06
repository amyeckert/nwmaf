<?php

namespace DeliciousBrains\WPMDB\SiteMigration\TPF;

use DeliciousBrains\WPMDB\Common\Transfers\Files\Excludes;

class ThemePluginFilesAddon extends \DeliciousBrains\WPMDB\Common\TPF\ThemePluginFilesAddon
{
    public function register()
    {
        parent::register();
        add_filter('wpmdb_excluded_plugins', [$this, 'filter_excluded_plugins']);
        add_filter('wpmdb_excluded_muplugins', [$this, 'filter_excluded_muplugins']);
        add_filter('wpmdb_excluded_other_files', [$this, 'filter_excluded_other_files']);
        add_filter('wpmdb_filter_files_list', [$this, 'filter_files_list']);
    }

    /**
     * Filters the list of excluded WPE other files
     *
     * @param array $files
     *
     * @return array
     * @handles wpmdb_excluded_other_files
     */
    public function filter_excluded_other_files($files)
    {
        $excluded_files = [
            'mysql.sql',
            'advanced-cache.php',
            'object-cache.php',
            'db-error.php',
            '.htaccess'
        ];

        if ( ! is_array($files)) {
            $files = [];
        }

        return array_merge($files, $excluded_files);
    }

    /**
     * Filters the list of excluded WPE MU plugins
     *
     * @param array $plugins
     *
     * @return array
     * @handles wpmdb_excluded_muplugins
     */
    public function filter_excluded_muplugins($plugins)
    {
        $excluded_muplugins = [
            'mu-plugin.php',
            'slt-force-strong-passwords.php',
            'wpengine-security-auditor.php',
            'stop-long-comments.php',
            'force-strong-passwords',
            'wpengine-common',
            'wpe-wp-sign-on-plugin',
            'wpe-wp-sign-on-plugin.php',
            'wpe-elasticpress-autosuggest-logger',
            'wpe-cache-plugin',
            'wpe-cache-plugin.php',
            '.htaccess'
        ];

        if ( ! is_array($plugins)) {
            $plugins = [];
        }

        return array_merge($plugins, $excluded_muplugins);
    }

    /**
     * Filters the list of excluded BlogVault plugins
     *
     * @param array $plugins
     *
     * @return array
     * @handles wpmdb_excluded_plugins
     */
    public function filter_excluded_plugins($plugins)
    {
        $excluded_plugins = [
            'quick-cache',
            'quick-cache-pro',
            'w3-total-cache',
            'wp-cache',
            'wp-file-cache',
            'wp-super-cache',
            'hyper-cache',
            'db-cache-reloaded-fix',
            'bv-cloudways-automated-migration',
            'wp-site-migrate',
            'migrate-to-liquidweb',
            'migrate-to-wefoster',
            'migrate-to-guru',
            'bv-pantheon-migration',
            'savvii-wp-migrate',
            'flywheel-migrations',
            'pressable-automated-migration',
            'migrate-to-pressable',
            'wpengine-ssl-helper',
            'wp-engine-ssl-helper',
            'limit-login-attempts',
            'force-strong-passwords',
            'sg-cachepress',
            'wpe-site-migration'
        ];

        if ( ! is_array($plugins)) {
            $plugins = [];
        }

        return array_merge($plugins, $excluded_plugins);
    }

    /**
     * Filters the list of files that's displayed in different UI panels.
     *
     * @param array $files
     *
     * @handles wpmdb_filter_files_list
     * @return array
     */
    public function filter_files_list($files)
    {
        if ( ! is_array($files) || empty($files)) {
            return $files;
        }

        return array_filter($files, function ($file) {
            $excludes = ['.htaccess*'];

            return ! Excludes::shouldExcludeFile($file[0]['path'], $excludes);
        });
    }
}
