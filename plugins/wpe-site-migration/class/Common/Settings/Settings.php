<?php

namespace DeliciousBrains\WPMDB\Common\Settings;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Helpers;
use DeliciousBrains\WPMDB\Common\Util\Util;

class Settings
{
    const DEFAULT_KEY_REGENERATION_INTERVAL = 0;

    /**
     * @var Util
     */
    public $util;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var
     */
    private static $static_settings;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Util $util, Filesystem $filesystem)
    {
        $this->util       = $util;
        $this->filesystem = $filesystem;

        // Late bind to allow for alteration of key regeneration interval etc.
        add_filter('wpmdb_filter_settings', [$this, 'maybe_regenerate_expired_key'], 20);
    }

    /**
     * Get the value for a specific settings key.
     *
     * @param string $setting
     *
     * @return mixed
     */
    public static function get_setting($setting)
    {
        if (is_array(static::$static_settings) && isset(static::$static_settings[$setting])) {
            return static::$static_settings[$setting];
        }

        throw new \InvalidArgumentException(__('Setting does not exist', 'wp-migrate-db'));
    }

    public function get_settings_for_frontend()
    {
        // Always get fresh settings for the frontend.
        $this->load_settings();
        $existing_settings = $this->settings;

        if ( ! empty($existing_settings['licence'])) {
            $masked_licence                      = $this->util->mask_licence($existing_settings['licence']);
            $existing_settings['masked_licence'] = $masked_licence;
        }

        $existing_settings['plugins'] = $this->filesystem->get_local_plugins();

        $existing_settings['key_expires_timestamp']    = static::key_expires_timestamp();
        $existing_settings['key_expires_sql_datetime'] = static::key_expires_sql_datetime();

        return $existing_settings;
    }

    /**
     * Get settings.
     *
     * @return array
     */
    public function get_settings()
    {
        if (empty($this->settings)) {
            $this->load_settings();
        }

        return $this->settings;
    }

    /**
     * Load settings from database and populate properties.
     *
     * @return void
     */
    public function load_settings()
    {
        $update_settings = false;
        $this->settings  = get_site_option(WPMDB_SETTINGS_OPTION);

        $default_settings = array(
            'key'                        => $this->util->generate_key(),
            'key_regenerated'            => time(),
            // Use default key regeneration interval unless upgrading, as previously keys never expired.
            'key_regeneration_interval'  => ! is_array($this->settings) || empty($this->settings['key']) ? static::get_default_key_regeneration_interval() : 0,
            'allow_connection'           => true,
            'allow_pull'                 => false,
            'allow_push'                 => false,
            'profiles'                   => array(),
            'licence'                    => '',
            'verify_ssl'                 => false,
            'whitelist_plugins'          => array(),
            'max_request'                => min(1024 * 1024, $this->util->get_bottleneck('max')),
            'delay_between_requests'     => 0,
            'prog_tables_hidden'         => true,
            'pause_before_finalize'      => false,
            'allow_tracking'             => null,
            'high_performance_transfers' => false,
        );

        // if we still don't have settings exist this must be a fresh install, set up some default settings
        if (false === $this->settings) {
            $this->settings  = $default_settings;
            $update_settings = true;
        } else {
            /*
             * When new settings are added an existing customer's db won't have the new settings.
             * They're added here to circumvent array index errors in debug mode.
             */
            foreach ($default_settings as $key => $value) {
                if ( ! isset($this->settings[$key])) {
                    $this->settings[$key] = $value;
                    $update_settings      = true;
                }
            }
        }

        $is_compat_mode = $this->util->is_muplugin_installed();

        if ( ! isset($this->settings['compatibility_mode']) || $is_compat_mode !== $this->settings['compatibility_mode']) {
            //override compatibility mode
            $this->settings['compatibility_mode'] = $is_compat_mode;
            $update_settings                      = true;
        }

        $filtered_settings = apply_filters('wpmdb_filter_settings', $this->settings);

        if ($this->settings !== $filtered_settings) {
            $this->settings  = $filtered_settings;
            $update_settings = true;
        }

        if ($update_settings) {
            update_site_option(WPMDB_SETTINGS_OPTION, $this->settings);
        }

        $user_licence = Helpers::get_user_licence_key();
        if ($user_licence) {
            $this->settings['licence'] = $user_licence;
        }

        self::$static_settings = $this->settings;
    }

    /**
     * Get the default number of seconds used before a secret key is automatically regenerated.
     *
     * @return int
     */
    public static function get_default_key_regeneration_interval()
    {
        /**
         * Filter the default number of seconds used before a secret key is automatically regenerated.
         *
         * Must be greater than or equal to zero.
         * Must be less than or equal to a year (YEAR_IN_SECONDS).
         * Zero means do not automatically regenerate.
         *
         * @param int $seconds
         */
        $seconds = apply_filters('wpmdb_default_key_regeneration_interval', self::DEFAULT_KEY_REGENERATION_INTERVAL);

        return static::sanitize_key_regeneration_interval($seconds);
    }

    /**
     * Get the array of intervals that are allowed to be used to set the
     * number of seconds before a secret key is automatically regenerated.
     *
     * Array has an integer key of seconds, value is a description of the interval.
     *
     * @return [int]string
     */
    public static function get_key_regeneration_intervals()
    {
        $default_intervals = [
            0                    => __('Never', 'wp-migrate-db'),
            HOUR_IN_SECONDS * 2  => __('2 hours', 'wp-migrate-db'),
            HOUR_IN_SECONDS * 8  => __('8 hours', 'wp-migrate-db'),
            DAY_IN_SECONDS       => __('1 day', 'wp-migrate-db'),
            DAY_IN_SECONDS * 3   => __('3 days', 'wp-migrate-db'),
            DAY_IN_SECONDS * 5   => __('5 days', 'wp-migrate-db'),
            WEEK_IN_SECONDS      => __('1 week', 'wp-migrate-db'),
            WEEK_IN_SECONDS * 2  => __('2 weeks', 'wp-migrate-db'),
            WEEK_IN_SECONDS * 28 => __('4 weeks', 'wp-migrate-db'),
            DAY_IN_SECONDS * 90  => __('90 days', 'wp-migrate-db'),
            DAY_IN_SECONDS * 180 => __('180 days', 'wp-migrate-db'),
            DAY_IN_SECONDS * 365 => __('365 days', 'wp-migrate-db'),
        ];

        /**
         * Filter the array of intervals that are allowed to be used to set the
         * number of seconds before a secret key is automatically regenerated.
         *
         * Array has an integer key of seconds, value is a description of the interval.
         *
         * @param [int]string $intervals
         */
        $intervals = apply_filters('wpmdb_key_regeneration_intervals', $default_intervals);

        if (empty($intervals) || ! is_array($intervals)) {
            $intervals = $default_intervals;
        }

        ksort($intervals, SORT_NUMERIC);

        return $intervals;
    }

    /**
     * Ensure that key regeneration interval is valid, set it to default value if not.
     *
     * @param int $seconds
     *
     * @return int
     */
    protected static function sanitize_key_regeneration_interval($seconds)
    {
        if ( ! is_int($seconds)) {
            $seconds = self::DEFAULT_KEY_REGENERATION_INTERVAL;
        }

        if ( ! key_exists($seconds, static::get_key_regeneration_intervals())) {
            // Even if this constant isn't in the allow list, at least we know it is a sensible value.
            $seconds = self::DEFAULT_KEY_REGENERATION_INTERVAL;
        }

        return min(max($seconds, 0), YEAR_IN_SECONDS);
    }

    /**
     * Maybe regenerate the secret key if it has expired.
     *
     * @param array $settings
     *
     * @return array
     *
     * @handles wpmdb_filter_settings
     */
    public function maybe_regenerate_expired_key($settings)
    {
        if ( ! is_array($settings) || empty($settings['key_regeneration_interval'])) {
            return $settings;
        }

        // If last key regeneration time invalid, or key has expired, regenerate it.
        if (
            empty($settings['key_regenerated']) ||
            ! is_int($settings['key_regenerated']) ||
            time() - abs($settings['key_regenerated']) > abs((int)$settings['key_regeneration_interval'])
        ) {
            $settings['key']             = $this->util->generate_key();
            $settings['key_regenerated'] = time();
        }

        return $settings;
    }

    /**
     * Does the secret ket expire?
     *
     * @return bool
     */
    public static function key_expires()
    {
        return ! empty(static::get_setting('key')) && ! empty(static::get_setting('key_regeneration_interval'));
    }

    /**
     * Get the timestamp for when the secret is next going to be regenerated.
     *
     * If the secret key never expires, returns false.
     *
     * @return false|int
     */
    public static function key_expires_timestamp()
    {
        if ( ! static::key_expires()) {
            return false;
        }

        $key_regenerated           = abs((int)static::get_setting('key_regenerated'));
        $key_regeneration_interval = abs((int)static::get_setting('key_regeneration_interval'));

        return $key_regenerated + $key_regeneration_interval;
    }

    /**
     * Get the SQL datetime format string for when the secret is next going to be regenerated.
     *
     * If the secret key never expires, returns empty string.
     *
     * @return string
     */
    public static function key_expires_sql_datetime()
    {
        $sql_datetime = '';
        $timestamp    = static::key_expires_timestamp();

        if ($timestamp) {
            if (function_exists('wp_date')) {
                $sql_datetime = wp_date('Y-m-d H:i:s', $timestamp);
            } else {
                $sql_datetime = date_i18n('Y-m-d H:i:s', $timestamp, true);
            }
        }

        return $sql_datetime;
    }
}
