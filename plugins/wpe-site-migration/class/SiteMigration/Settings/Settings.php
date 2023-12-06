<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Settings;

use DeliciousBrains\WPMDB\Common\Settings\Settings as Common_Settings;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms\Flywheel;
use DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms\Platforms;
use DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms\WPEngine;

class Settings
{
    const DEFAULT_KEY_REGENERATION_INTERVAL = WEEK_IN_SECONDS;

    public function __construct()
    {
        add_filter('wpmdb_filter_settings', [$this, 'settings']);
        add_filter('wpmdb_default_key_regeneration_interval', [$this, 'filter_default_key_regeneration_interval']);
        add_filter('wpmdb_key_regeneration_intervals', [$this, 'filter_key_regeneration_intervals']);
    }

    /**
     * add hooks
     *
     **/
    public function register()
    {
        add_filter('wpmdb_notification_strings', [$this, 'notifications']);
    }

    /**
     * Get a list of platforms that the plugin supports migrating to.
     *
     * @return array
     */
    public static function target_platforms()
    {
        return apply_filters('wpmdb_target_platforms', [
            WPEngine::get_key(),
            Flywheel::get_key(),
        ]);
    }

    /**
     * Is the current site a target platform to be migrated to?
     *
     * @return bool
     */
    public function is_target_platform()
    {
        $platform = Platforms::get_platform();

        return ! empty($platform) && in_array($platform, static::target_platforms());
    }

    /**
     * Filter the settings specific to WPE
     *
     * @param array $settings
     *
     * @return array
     **/
    public function settings(array $settings)
    {
        if ($this->is_target_platform()) {
            $settings['allow_connection']          = true;
            $settings['allow_push']                = true;
            $settings['key_regenerated']           = empty($settings['key']) || empty($settings['key_regenerated']) ? time() : $settings['key_regenerated'];
            $settings['key_regeneration_interval'] = empty($settings['key_regeneration_interval']) ? Common_Settings::get_default_key_regeneration_interval() : $settings['key_regeneration_interval'];
            $settings['key']                       = empty($settings['key']) ? Util::generate_key() : $settings['key'];
        } else {
            $settings['allow_connection'] = false;
            $settings['allow_push']       = false;
            $settings['key']              = '';
        }

        $settings['allow_pull']     = false;
        $settings['allow_tracking'] = true;

        return $settings;
    }

    /**
     * Filter notifications to potentially notices for found problems.
     *
     * @param array $notifications
     *
     * @return array
     */
    public function notifications($notifications)
    {
        if ( ! is_array($notifications) || empty($notifications)) {
            return $notifications;
        }

        $notice_id = 'block_external_warning';

        if ( ! empty($notifications[$notice_id])) {
            $msg = '<div>';
            $msg .= sprintf(
                __(
                    'We\'ve detected that <code>WP_HTTP_BLOCK_EXTERNAL</code> is enabled, which will prevent WP Engine Site Migration from functioning properly. You should either disable <code>WP_HTTP_BLOCK_EXTERNAL</code> or add any sites that you\'d like to migrate to <code>WP_ACCESSIBLE_HOSTS</code>. More information on this can be found <a href="%s" target="_blank">here</a>.',
                    'wp-migrate-db'
                ),
                'https://deliciousbrains.com/wp-migrate-db-pro/doc/wp_http_block_external/?utm_campaign=error%2Bmessages&utm_source=WPESM&utm_medium=insideplugin'
            );
            $msg .= '</div>';

            $notifications[$notice_id]['message'] = $msg;
        }

        return $notifications;
    }

    /**
     * Filter the default key regeneration interval.
     *
     * @param int $seconds
     *
     * @return int
     */
    public function filter_default_key_regeneration_interval($seconds)
    {
        return self::DEFAULT_KEY_REGENERATION_INTERVAL;
    }

    /**
     * Filter the allowed key regeneration intervals to a smaller set.
     *
     * @param array $intervals
     *
     * @return array
     */
    public function filter_key_regeneration_intervals($intervals)
    {
        return [
            HOUR_IN_SECONDS * 2 => __('2 hours', 'wp-migrate-db'),
            HOUR_IN_SECONDS * 8 => __('8 hours', 'wp-migrate-db'),
            DAY_IN_SECONDS      => __('1 day', 'wp-migrate-db'),
            DAY_IN_SECONDS * 3  => __('3 days', 'wp-migrate-db'),
            DAY_IN_SECONDS * 5  => __('5 days', 'wp-migrate-db'),
            WEEK_IN_SECONDS     => __('1 week', 'wp-migrate-db'),
        ];
    }
}
