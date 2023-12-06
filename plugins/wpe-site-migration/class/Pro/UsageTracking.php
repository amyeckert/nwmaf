<?php

namespace DeliciousBrains\WPMDB\Pro;

use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration;
use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationProcess;
use DeliciousBrains\WPMDB\Common\EntitlementsInterface;
use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Pro\UI\Template;
use WP_Error;

class UsageTracking
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var Properties
     */
    private $props;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ErrorLog
     */
    private $error_log;

    /**
     * @var Template
     */
    private $template;

    /**
     * @var FormData
     */
    private $form_data;

    /**
     * @var string
     */
    private $api_url;

    /**
     * @var EntitlementsInterface
     */
    private $license;

    public function __construct(
        Settings $settings,
        Filesystem $filesystem,
        ErrorLog $error_log,
        Template $template,
        FormData $form_data,
        Properties $properties,
        EntitlementsInterface $license
    ) {
        $this->settings   = $settings->get_settings();
        $this->props      = $properties;
        $this->filesystem = $filesystem;
        $this->error_log  = $error_log;
        $this->template   = $template;
        $this->form_data  = $form_data;
        $this->license    = $license;

        $this->api_url = apply_filters('wpmdb_logging_endpoint_url', 'https://api2.deliciousbrains.com');

        add_action('wpmdb_migration_started', [$this, 'log_migration_start']);
        add_action('wpmdb_task_item', [$this, 'log_migration_in_progress'], 20, 3);
        add_action('wpmdb_track_migration_cancel', [$this, 'log_migration_cancellation']);
        add_action('wpmdb_track_migration_error', [$this, 'log_migration_error']);
        add_action('wpmdb_after_finalize_migration', [$this, 'log_migration_complete']);
    }

    public function register()
    {
        add_action('wpmdb_additional_settings_advanced', array($this, 'template_toggle_usage_tracking'));
        add_filter('wpmdb_notification_strings', [$this, 'template_notice_enable_usage_tracking']);
    }

    /**
     * Send migration update to usage DB
     *
     * @param string $status complete|error|cancelled
     * @param array  $data
     *
     * @return void
     */
    public function send_migration_update($status = 'complete', $data = [])
    {
        $state_data = Persistence::getStateData();

        if (is_wp_error($state_data)) {
            return;
        }

        if (empty($this->settings['allow_tracking'])) {
            return;
        }

        $migration_guid = isset($state_data['migration_state_id']) ? $state_data['migration_state_id'] : '';

        if (empty($migration_guid) && ! empty($state_data['form_data'])) {
            $form_data      = json_decode($state_data['form_data']);
            $migration_guid = $form_data->current_migration->migration_id;
        }

        $log_data = [
            'migration_guid'   => $migration_guid,
            'migration_status' => $status,
            'last_stage'       => isset($state_data['stage']) ? $state_data['stage'] : null,
            'error_text'       => isset($data['error_text']) ? $data['error_text'] : null,
            'error_code'       => isset($data['error_code']) ? $data['error_code'] : null,
            'error_data'       => isset($data['error_data']) ? $data['error_data'] : null,
            'migration_stats'  => Persistence::getMigrationStats(),
        ];

        // When processing an end of migration update, set the completion time.
        if ('in-progress' !== $status) {
            $log_data['migration_complete_time'] = time();
        }

        $log_data = apply_filters('wpmdb_usage_tracking_update_data', $log_data);

        $remote_post_args = array(
            'timeout'            => 60,
            'method'             => 'POST',
            'headers'            => array('Content-Type' => 'application/json'),
            'body'               => json_encode($log_data),
            'reject_unsafe_urls' => false,
        );

        $api_url = $this->api_url . '/complete';

        $result = wp_remote_post($api_url, $remote_post_args);

        if (is_wp_error($result) || $result['response']['code'] >= 400) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error logging migration event');
                error_log(print_r($result, true));
            }
            $this->error_log->log_error('Error logging Migration event', $result);
        }
    }

    /**
     * Log migration in-progress update.
     *
     * @param array|bool|WP_Error      $item       The background migration batch item.
     * @param BackgroundMigration|null $migration  The background migration.
     * @param string                   $identifier The background migration process identifier.
     *
     * @return array|bool|WP_Error
     *
     * @handles wpmdb_task_item
     */
    public function log_migration_in_progress($item, $migration, $identifier)
    {
        if (empty($identifier)) {
            return $item;
        }

        $timeout_key = $identifier . '_in_progress_timeout';

        // At the end of a migration, just clear out the in progress message timeout.
        if (false === $item) {
            delete_site_transient($timeout_key);

            return $item;
        }

        // Have everything we need to attempt to send an in-progress message?
        if (
            empty($item) ||
            is_wp_error($item) ||
            ! is_array($item) ||
            empty($item['migration_id']) ||
            BackgroundMigrationProcess::all_stages_processed($item) ||
            empty($migration)
        ) {
            return $item;
        }

        // Get the migration ID as we'll need it from here on down.
        $migration_id = $item['migration_id'];

        // Is there a timeout record for a previous migration that should be
        // cleaned up before we start sending our first in-progress message?
        $timeout = get_site_transient($timeout_key);

        if ( ! empty($timeout) && $migration_id !== $timeout) {
            delete_site_transient($timeout_key);
        } elseif ( ! empty($timeout)) {
            // Migration's timeout hasn't been reached yet.
            return $item;
        }

        /**
         * Change how often in-progress updates are sent.
         *
         * Returning zero disables sending in-progress updates.
         *
         * @param int $seconds Default is 300 (every 5 minutes).
         */
        $interval = apply_filters('wpmdb_in_progress_tracking_interval', 5 * MINUTE_IN_SECONDS);

        if ( ! is_int($interval) || empty(max(0, $interval))) {
            return $item;
        }

        // Log an in-progress update if we can successfully set a timeout until the next update.
        if (set_site_transient($timeout_key, $migration_id, $interval)) {
            $this->send_migration_update('in-progress');
        }

        return $item;
    }

    /**
     * Log migration complete
     *
     * @return void
     *
     * @handles wpmdb_after_finalize_migration
     */
    public function log_migration_complete()
    {
        $this->send_migration_update();
    }

    /**
     * Log migration cancellation
     *
     * @return void
     *
     * @handles wpmdb_track_migration_cancel
     */
    public function log_migration_cancellation()
    {
        $this->send_migration_update('cancelled');
    }

    /**
     * Logs migration error
     *
     * @param array $errors
     *
     * @handles wpmdb_track_migration_error
     */
    public function log_migration_error($errors)
    {
        $data = [
            'error_code' => $errors['code'],
            'error_text' => $errors['message'],
            'error_data' => $errors['data'],
        ];

        $this->send_migration_update('error', $data);
    }

    /**
     * Log Migration Start Event
     *
     * @param string $migration_id
     *
     * @return void
     *
     * @handles wpmdb_migration_started
     */
    public function log_migration_start($migration_id)
    {
        $license_key = $this->license->get_licence_key();
        if (Util::appEnv() === 'pro' && empty($license_key)) {
            return;
        }

        $state_data = Persistence::getStateData();

        if (is_wp_error($state_data) || empty($migration_id)) {
            return;
        }

        $settings = $this->settings;

        if (empty($settings['allow_tracking'])) {
            return;
        }

        do_action('wpmdb_log_migration_event', $state_data);

        $api_url  = $this->api_url . '/event';
        $cookie   = false === Persistence::getRemoteWPECookie() ? 0 : 1;
        $log_data = array(
            'local_timestamp'                        => time(),
            'licence_key'                            => $license_key,
            'cli'                                    => false, // TODO: Is this needed, useful, and obtainable?
            'setting-compatibility_plugin_installed' => $this->filesystem->file_exists($this->props->mu_plugin_dest),
            'remote_cookie'                          => $cookie,
            'local_platform'                         => $state_data['site_details']['local']['platform'],
            'remote_platform'                        => $state_data['site_details']['remote']['platform'],
        );
        if (array_key_exists('site_migration', $state_data)) {
            $log_data['site_migration'] = $state_data['site_migration'];
        }

        // ***+=== @TODO - revisit usage of parse_migration_form_data
        foreach ($this->form_data->parse_and_save_migration_form_data($state_data['form_data']) as $key => $val) {
            if ('connection_info' === $key) {
                continue;
            }

            //remove items added for background processing
            $properties_to_skip = ['remote_site', 'local_site', 'media_files', 'theme_plugin_files'];
            if ( ! in_array($key, $properties_to_skip)) {
                $log_data['profile-' . $key] = $val;
            }
        }

        foreach ($settings as $key => $val) {
            if ('profiles' === $key || 'key' === $key) {
                continue;
            }
            $log_data['setting-' . $key] = $val;
        }

        foreach ($GLOBALS['wpmdb_meta'] as $plugin => $arr) {
            $log_data[$plugin . '-active']  = true;
            $log_data[$plugin . '-version'] = $arr['version'];
        }

        foreach ($state_data['site_details'] as $site => $info) {
            $log_data[$site . '-site_url'] = $info['site_url'];
            $log_data[$site . '-home_url'] = $info['home_url'];
            $log_data[$site . '-prefix']   = $info['prefix'];

            $log_data[$site . '-is_multisite'] = $info['is_multisite'];

            if (isset($info['subsites']) && is_array($info['subsites'])) {
                $log_data[$site . '-subsite_count'] = count($info['subsites']);
            }

            $log_data[$site . '-is_subdomain_install'] = $info['is_subdomain_install'];

            if (isset($info['sizes']) && is_array($info['sizes'])) {
                foreach ($info['sizes'] as $size_key => $size_value) {
                    $log_data[$site . '-' . $size_key] = $size_value;
                }
            }

            $log_data[$site . '-pwp_name'] = empty($info['pwp_name']) ? '' : $info['pwp_name'];
        }

        $diagnostic_log = [];

        foreach ($this->error_log->get_diagnostic_info() as $group_name => $data) {
            foreach ($data as $key => $val) {
                if (0 === $key) {
                    continue;
                }
                $key_name = $group_name;
                if (is_string($key)) {
                    $key_name .= "-{$key}";
                }
                $diagnostic_log[$key_name] = $val;
            }
        }

        $log_data['diagnostic_log'] = $diagnostic_log;

        foreach ($log_data as $key => $val) {
            if (strpos($key, 'count') !== false || is_array($val)) {
                continue;
            }
            if ('1' === $val) {
                $log_data[$key] = true;
                continue;
            }
            if ('0' === $val) {
                $log_data[$key] = false;
                continue;
            }
            if ('true' === $val) {
                $log_data[$key] = true;
                continue;
            }
            if ('false' === $val) {
                $log_data[$key] = false;
            }
        }

        $log_data['migration_guid'] = $migration_id;

        $remote_post_args = array(
            'timeout'            => 60,
            'method'             => 'POST',
            'headers'            => array('Content-Type' => 'application/json'),
            'body'               => json_encode($log_data),
            'reject_unsafe_urls' => false,
        );

        $result = wp_remote_post($api_url, $remote_post_args);
        if (is_wp_error($result) || $result['response']['code'] >= 400) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Error logging migration event');
                error_log(print_r($result, true));
            }
            $this->error_log->log_error('Error logging Migration event', $result);
        }
    }

    public function template_notice_enable_usage_tracking($notifications)
    {
        if (Util::isPro() && ! is_bool($this->settings['allow_tracking'])) {
            $notifications['notice-enable-usage-tracking'] = [
                'message'     => $this->template->template_to_string('notice-enable-usage-tracking', 'pro'),
                'link'        => false,
                'id'          => 'notice-enable-usage-tracking',
                'custom_link' => 'usage_tracking',
            ];
        }

        return $notifications;
    }
}
