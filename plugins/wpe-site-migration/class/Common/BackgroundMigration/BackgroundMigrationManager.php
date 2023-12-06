<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\WPMDBRestAPIServer;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\TransportManager;
use UnexpectedValueException;
use WP_Error;
use WP_REST_Request;

/**
 * Background Migration Manager
 *
 * Start, stop, pause, resume and get information about background migrations.
 */
class BackgroundMigrationManager
{
    /**
     * The cron schedule identifier used for health checks.
     */
    const CRON_INTERVAL_IDENTIFIER = WPMDB_OPTION_PREFIX . 'migration_cron_interval';

    /**
     * The last migration user meta key.
     */
    const LAST_MIGRATION_USERMETA_IDENTIFIER = WPMDB_OPTION_PREFIX . "last_migration";

    /**
     * @var WPMDBRestAPIServer
     */
    private $rest_API_server;

    /**
     * @var Http
     */
    private $http;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * An array of registered migrations that can be run.
     *
     * @var BackgroundMigration[]
     */
    private $migrations = [];

    /**
     * @var bool|Callable
     */
    private $callable_shutdown_handler = false;

    /**
     * Instantiate the Background Migration Manager.
     *
     * @param WPMDBRestAPIServer $rest_API_server
     * @param Http               $http
     */
    public function __construct(
        WPMDBRestAPIServer $rest_API_server,
        Http $http,
        Settings $settings
    ) {
        $this->rest_API_server = $rest_API_server;
        $this->http            = $http;
        $this->settings        = $settings;

        // Set a single cron schedule for all migration types as only one can be run at a time.
        add_filter('cron_schedules', array($this, 'schedule_cron_healthcheck'));

        // Keep track of updates to the batch item.
        add_filter('wpmdb_task_item', [$this, 'save_last_migration'], 10, 3);

        // Maybe add basic auth credentials to requests
        add_filter('http_request_args', [$this, 'filter_basic_auth_credentials'], 10, 2);

        // Allow a background migration shutdown handler to be (un)registered.
        add_action('wpmdb_register_background_shutdown_handler', array($this, 'register_shutdown_handler'), 10, 2);
        add_action('wpmdb_unregister_background_shutdown_handler', array($this, 'unregister_shutdown_handler'));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Background migration processes must be instantiated early to handle dispatched callback.
        $this->register_migrations();

        add_action('rest_api_init', [$this, 'register_routes']);
        add_filter('wpmdb_data', [$this, 'filter_wpmdb_data']);
        add_filter('wpmdb_usage_tracking_update_data', [$this, 'filter_wpmdb_usage_tracking_update_data']);
    }

    /**
     * Register available background migrations.
     *
     * @return void
     */
    private function register_migrations()
    {
        /**
         * Register background migrations.
         *
         * @param BackgroundMigration[] $migrations
         *
         * @returns BackgroundMigration[]
         */
        $migrations = apply_filters('wpmdb_register_background_migrations', []);

        foreach ($migrations as $type => $migration) {
            if (
                is_a(
                    $migration,
                    'DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration'
                )
            ) {
                $this->migrations[$type] = $migration;
            }
        }
    }

    /**
     * Queries the migration process lock from the DB.
     *
     * @param string $identifier
     *
     * @return null|string
     */
    private function get_migration_process_lock($identifier)
    {
        global $wpdb;

        $status = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1",
                '_site_transient_' . $identifier . '_process_lock'
            )
        );

        return $status;
    }

    /**
     * Returns the file transport method name from the current migration state.
     *
     * @param string $migration_id
     *
     * @return mixed|null
     */
    private function get_migration_file_transport_method($migration_id)
    {
        try {
            $current_migration = StateFactory::create('current_migration')->load_state($migration_id);

            return $current_migration->get(TransportManager::TRANSPORT_METHOD_PROPERTY);
        } catch (UnexpectedValueException $exception) {
            return null;
        }
    }

    /**
     * Get the migration next health check schedule.
     *
     * @param string $identifier
     *
     * @return int|false
     */
    private function get_migration_health_check_cron_schedule($identifier)
    {
        return wp_next_scheduled($identifier . '_cron');
    }

    /**
     * Register the REST routes for the background migration manager.
     *
     * @return void
     */
    public function register_routes()
    {
        $this->rest_API_server->registerRestRoute(
            'migration',
            [
                'methods'  => 'GET',
                'callback' => [$this, 'ajax_get_migration'],
            ]
        );

        $this->rest_API_server->registerRestRoute(
            'migration',
            [
                'methods'  => 'PUT',
                'callback' => [$this, 'ajax_put_migration'],
                'args'     => [
                    'action'       => [
                        'description' => esc_html__(
                            'The action to perform, one of "start", "cancel", "pause_resume".',
                            'wp-migrate-db'
                        ),
                        'type'        => 'string',
                        'required'    => true,
                    ],
                    'type'         => [
                        'description' => esc_html__(
                            'The type of migration to perform the action on, e.g. "export", "find_replace" etc.',
                            'wp-migrate-db'
                        ),
                        'type'        => 'string',
                        'required'    => true,
                    ],
                    'migration_id' => [
                        'description' => esc_html__(
                            'The ID of the current migration, only required when starting a migration.',
                            'wp-migrate-db'
                        ),
                        'type'        => 'string',
                        'required'    => false,
                    ],
                ],
            ]
        );

        // A DELETE endpoint to enable dismissing a user's last migration.
        $this->rest_API_server->registerRestRoute(
            'migration',
            [
                'methods'  => 'DELETE',
                'callback' => [$this, 'ajax_delete_migration'],
                'args'     => [
                    'migration_id' => [
                        'description' => esc_html__(
                            'The ID of the migration to be dismissed.',
                            'wp-migrate-db'
                        ),
                        'type'        => 'string',
                        'required'    => true,
                    ],
                ],
            ]
        );
    }

    /**
     * Get information about the currently running migration.
     *
     * @return void
     *
     * TODO: This could be changed to return info for all known "migrations"?
     */
    public function ajax_get_migration()
    {
        $this->http->end_ajax($this->get_background_migrations_info());
    }

    /**
     * Control the currently running migration.
     *
     * @param WP_REST_Request $request
     *
     * @return void
     */
    public function ajax_put_migration(WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        if (empty($data['action'])) {
            $this->http->end_ajax(
                new WP_Error(
                    'missing-action',
                    __('Action not supplied.', 'wp-migrate-db')
                )
            );

            return;
        }

        if (empty($data['type'])) {
            $this->http->end_ajax(
                new WP_Error(
                    'missing-type',
                    __('Type not supplied.', 'wp-migrate-db')
                )
            );

            return;
        }

        $data['migration_id'] = ! empty($data['migration_id']) ? $data['migration_id'] : null;

        $result = $this->perform_action(
            $data['type'],
            $data['action'],
            $data['migration_id']
        );

        if (is_wp_error($result) || ! is_bool($result)) {
            $this->http->end_ajax($result);

            return;
        }

        // Try and get currently active migration to respond with.
        $migration = $this->get_active_migration();

        if (
            is_a(
                $migration,
                'DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration'
            )
        ) {
            $migration = $migration->get_info();
        } else {
            $migration = false;
        }

        $response = array(
            'ok'        => $result,
            'migration' => $migration,
        );

        $this->http->end_ajax($response);
    }

    /**
     * Dismiss the user's last migration details.
     *
     * @param WP_REST_Request $request
     *
     * @return void
     */
    public function ajax_delete_migration(WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        if (empty($data['migration_id'])) {
            $this->http->end_ajax(
                new WP_Error(
                    'missing-migration-id',
                    __('Migration ID not supplied.', 'wp-migrate-db')
                )
            );

            return;
        }

        $last_migration = $this->get_last_migration();

        if (
            is_array($last_migration) &&
            empty($last_migration['dismissed']) &&
            ! empty($last_migration['migration_id']) &&
            $data['migration_id'] === $last_migration['migration_id']
        ) {
            $last_migration['dismissed'] = true;

            $this->update_last_migration($last_migration);
        }

        // For anything that needs to be done after a migration is dismissed.
        do_action('wpmdb_migration_dismissed');

        // Reply with same data as GET migration, which will include updated last_migration.
        $this->ajax_get_migration();
    }

    /**
     * Get currently registered background migrations.
     *
     * @return BackgroundMigration[]
     */
    public function get_migrations()
    {
        return $this->migrations;
    }

    /**
     * Get migration.
     *
     * @param string $type
     *
     * @return BackgroundMigration|false
     */
    public function get_migration($type)
    {
        if (empty($type)) {
            return false;
        }

        if (array_key_exists($type, $this->migrations)) {
            return $this->migrations[$type];
        }

        return false;
    }

    /**
     * Get the currently active migration, or false if none active.
     *
     * @return BackgroundMigration|false
     */
    public function get_active_migration()
    {
        foreach ($this->get_migrations() as $migration) {
            if ($migration->is_active()) {
                return $migration;
            }
        }

        return false;
    }

    /**
     * Try and perform the requested action for a migration identified by its type.
     *
     * @param string      $type         Migration type, e.g. "export", "find_replace" etc.
     * @param string      $action       One of "start", "cancel" or "pause_resume".
     * @param string|null $migration_id Optional Migration ID, required for "start" action.
     *
     * @return bool|WP_Error
     */
    public function perform_action($type, $action, $migration_id = null)
    {
        $migration = $this->get_migration($type);

        if (
            ! is_a(
                $migration,
                'DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration'
            )
        ) {
            return new WP_Error(
                'invalid-type',
                sprintf(__('Invalid migration type "%s" supplied.', 'wp-migrate-db'), $type)
            );
        }

        if (
            ! in_array($action, array('start', 'cancel', 'pause_resume')) ||
            ! method_exists($migration, 'handle_' . $action)
        ) {
            return new WP_Error(
                'invalid-action',
                __('Invalid action supplied.', 'wp-migrate-db')
            );
        }

        $active_migration = $this->get_active_migration();

        // Only one migration can be running or interacted with at once, noop out.
        if (
            ! empty($active_migration) &&
            (
                'start' === $action ||
                $migration->get_type() !== $active_migration->get_type()
            )
        ) {
            return false;
        }

        // When starting a background migration we need to set the Migration ID.
        if ('start' === $action) {
            if (empty($migration_id)) {
                return new WP_Error(
                    'missing-migration-id',
                    __('Migration ID not supplied.', 'wp-migrate-db')
                );
            }

            if (false === $migration->set_current_migration_state($migration_id)) {
                return new WP_Error(
                    'invalid-migration-id',
                    __(
                        'Data for Migration ID could not be loaded.',
                        'wp-migrate-db'
                    )
                );
            }
        }

        // Set current migration state before handling the action
        if (empty($migration->get_current_migration_state())) {
            $migration->set_current_migration_state($migration_id);
        }

        call_user_func(array($migration, 'handle_' . $action));

        return true;
    }

    /**
     * Schedule the cron healthcheck job.
     *
     * @access public
     *
     * @param mixed $schedules Schedules.
     *
     * @return mixed
     */
    public function schedule_cron_healthcheck($schedules)
    {
        // Run health check every minute.
        $interval = apply_filters(self::CRON_INTERVAL_IDENTIFIER, 1);

        if (property_exists($this, 'cron_interval')) {
            $interval = apply_filters(self::CRON_INTERVAL_IDENTIFIER, $this->cron_interval);
        }

        if (1 === $interval) {
            $display = __('Every Minute');
        } else {
            $display = sprintf(__('Every %d Minutes'), $interval);
        }

        // Adds an "Every NNN Minute(s)" schedule to the existing cron schedules.
        $schedules[self::CRON_INTERVAL_IDENTIFIER] = array(
            'interval' => MINUTE_IN_SECONDS * $interval,
            'display'  => $display,
        );

        return $schedules;
    }

    /**
     * Parse a task item value that is about to be updated to save the last migration for the initiating user.
     *
     * @param array|bool|WP_Error      $item       The background migration batch item.
     * @param BackgroundMigration|null $migration  The background migration.
     * @param string                   $identifier The background migration process identifier
     */
    public function save_last_migration($item, $migration, $identifier)
    {
        if (
            is_wp_error($item) ||
            ! is_array($item) ||
            ! isset($item['started_by'], $item['started_at'], $item['migration_id'], $item['total'], $item['stages']) ||
            empty($migration)
        ) {
            return $item;
        }

        $last_migration  = $this->get_last_migration($item['started_by']);
        $processed_bytes = 0;
        $target_bytes    = 0;

        if (
            empty($last_migration) ||
            ! is_array($last_migration) ||
            empty($last_migration['migration_id']) ||
            $last_migration['migration_id'] !== $item['migration_id']
        ) {
            $last_migration              = $item;
            $last_migration['type']      = $migration::get_type();
            $last_migration['dismissed'] = false;
        } else {
            $target_bytes    = $last_migration['total']['target_bytes'];
            $processed_bytes = $last_migration['total']['processed_bytes'];
            $last_migration  = array_merge($last_migration, $item);
        }

        $last_migration['updated_at'] = time();

        // Calculate the target bytes change
        if ($target_bytes !== $item['total']['target_bytes']) {
            $last_migration['target_bytes_change']     = $item['total']['target_bytes'] - $target_bytes;
            $last_migration['target_bytes_changed_at'] = $last_migration['updated_at'];
        }

        // Calculate the processed bytes change
        if ($processed_bytes !== $item['total']['processed_bytes']) {
            $last_migration['processed_bytes_change']     = $item['total']['processed_bytes'] - $processed_bytes;
            $last_migration['processed_bytes_changed_at'] = $last_migration['updated_at'];
        }

        // Check if the process lock exists
        $process_lock_value = $this->get_migration_process_lock($identifier);

        if ( ! empty($process_lock_value)) {
            $last_migration['process_locked_at'] = (int)array_slice(explode(' ', $process_lock_value), -1)[0];
            $last_migration['process_locked']    = true;
        } else {
            $last_migration['process_locked'] = false;
        }

        // Check if the health check cron is scheduled
        $health_check_schedule = $this->get_migration_health_check_cron_schedule($identifier);

        if ( ! empty($health_check_schedule)) {
            $last_migration['healthcheck_cron_scheduled_for'] = $health_check_schedule;
            $last_migration['healthcheck_cron_scheduled']     = true;
        } else {
            $last_migration['healthcheck_cron_scheduled'] = false;
        }

        $last_migration['file_transport_method'] = $this->get_migration_file_transport_method($item['migration_id']);

        // Catch last update where migration either completed or errored out.
        if (empty($last_migration['finished']) && (BackgroundMigrationProcess::all_stages_processed($item) || BackgroundMigrationProcess::has_errors($item))) {
            $last_migration['finished']    = true;
            $last_migration['finished_at'] = $last_migration['updated_at'];
            $last_migration['success']     = BackgroundMigrationProcess::all_stages_processed($item);
        }

        $this->update_last_migration($last_migration, $item['started_by']);

        return $item;
    }

    /**
     * Get last migration's data if it exists.
     *
     * @param int|null $user_id
     *
     * @return false|array
     */
    private function get_last_migration($user_id = null)
    {
        $user_id = ! empty($user_id) ? $user_id : $this->active_migration_started_by();

        if (empty($user_id)) {
            return false;
        }

        $last_migration = get_user_meta($user_id, self::LAST_MIGRATION_USERMETA_IDENTIFIER, true);

        if (empty($last_migration) || is_wp_error($last_migration)) {
            return false;
        }

        return $last_migration;
    }

    /**
     * Updates the last migration user meta entry.
     *
     * @param array    $data
     * @param int|null $user_id the user ID of the migration owner
     *
     * @return void
     */
    private function update_last_migration($data, $user_id = null)
    {
        $user_id = ! empty($user_id) ? $user_id : get_current_user_id();
        update_user_meta($user_id, self::LAST_MIGRATION_USERMETA_IDENTIFIER, $data);
    }

    /**
     * Get data for both the active migration, and the last migration.
     *
     * @return array
     */
    private function get_background_migrations_info()
    {
        $data = [
            'active_migration' => false,
            'last_migration'   => $this->get_last_migration(),
            'settings'         => $this->settings->get_settings(),
        ];

        // Add a couple of pseudo settings.
        $data['settings']['key_expires_timestamp']    = Settings::key_expires_timestamp();
        $data['settings']['key_expires_sql_datetime'] = Settings::key_expires_sql_datetime();

        $active_migration = $this->get_active_migration();

        if (
            is_a(
                $active_migration,
                'DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration'
            )
        ) {
            $data['active_migration'] = $active_migration->get_info();
        }

        return $data;
    }

    /**
     * Adds the currently active migration info to the wpmdb_data localized object.
     *
     * @param array $data
     *
     * @return array
     * @handles wpmdb_data
     */
    public function filter_wpmdb_data($data)
    {
        $migration_id = null;

        $data = array_merge($data, $this->get_background_migrations_info());

        if ( ! empty($data['active_migration']['current_task']['migration_id'])) {
            $migration_id = $data['active_migration']['current_task']['migration_id'];
        } elseif ( ! empty($data['last_migration']['migration_id'])) {
            $migration_id = $data['last_migration']['migration_id'];
        }

        if ( ! empty($migration_id)) {
            $data['migration_state'] = [
                'current_migration' => StateFactory::create('current_migration')->load_state($migration_id)->get_state(),
                'remote_site'       => StateFactory::create('remote_site')->load_state($migration_id)->get_state(),
            ];
        }

        return $data;
    }

    /**
     * Filters http request arguments and adds basic auth credentials if they exist.
     *
     * @param array  $r
     * @param string $url
     *
     * @handle http_request_args
     *
     * @return array
     */
    public function filter_basic_auth_credentials($r, $url)
    {
        $stored_creds = Persistence::getLocalSiteBasicAuth();
        $credentials  = null;

        if (false !== $stored_creds) {
            $credentials = $stored_creds;
        } elseif ( ! empty($_SERVER['PHP_AUTH_USER']) && ! empty($_SERVER['PHP_AUTH_PW'])) {
            $credentials = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
            Persistence::storeLocalSiteBasicAuth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        }

        if (0 === strpos($url, home_url()) && ! empty($credentials)) {
            $r['headers']['Authorization'] = 'Basic ' . $credentials;
        }

        return $r;
    }

    /**
     * Filters batch information into the usage tracking data.
     *
     * @param array $data
     *
     * @handle wpmdb_usage_tracking_update_data
     *
     * @return array
     */
    public function filter_wpmdb_usage_tracking_update_data($data)
    {
        if ( ! is_array($data)) {
            return $data;
        }

        $data['batch_data'] = $this->get_last_migration();

        return $data;
    }

    /**
     * Register a shutdown handler.
     *
     * @param Callable $callable
     * @param array    $args
     *
     * @return void
     */
    public function register_shutdown_handler($callable, $args)
    {
        if (is_callable($callable)) {
            $this->callable_shutdown_handler = $callable;

            register_shutdown_function(array($this, 'shutdown_handler'), $args);
        } else {
            $this->unregister_shutdown_handler();
        }
    }

    /**
     * Unregister the current shutdown handler.
     *
     * @return void
     */
    public function unregister_shutdown_handler()
    {
        $this->callable_shutdown_handler = false;
    }

    /**
     * Proxy shutdown handler that calls the real shutdown handler.
     *
     * @param array $args
     *
     * @return void
     */
    public function shutdown_handler($args)
    {
        if (false !== $this->callable_shutdown_handler && is_callable($this->callable_shutdown_handler)) {
            $callable = $this->callable_shutdown_handler;
            $callable($args);
        }
    }

    /**
     * Get the user that started the currently active migration.
     *
     * Will fall back to current user.
     *
     * @return int
     */
    public function active_migration_started_by()
    {
        $user_id          = get_current_user_id();
        $active_migration = $this->get_active_migration();

        if (empty($active_migration)) {
            return $user_id;
        }

        if ( ! is_a($active_migration, BackgroundMigration::class)) {
            return $user_id;
        }

        $migration_info = $active_migration->get_info();

        if (is_array($migration_info) && ! empty($migration_info['current_task']['started_by'])) {
            $user_id = $migration_info['current_task']['started_by'];
        }

        return $user_id;
    }
}
