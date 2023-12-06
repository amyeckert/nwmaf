<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Exceptions\SanitizationFailureException;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Properties\DynamicProperties;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Sanitize;
use DeliciousBrains\WPMDB\Common\Util\Util;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Container;
use DeliciousBrains\WPMDB\WPMDBDI;
use DI\DependencyException;
use DI\NotFoundException;
use WP_Error;

class MigrationStateManager
{

    /**
     * @var ErrorLog
     */
    public $error_log;
    /**
     * @var Properties
     */
    public $props;
    /**
     * @var Util
     */
    public $util;
    /**
     * @var StateDataContainer
     */
    public $state_container;
    /**
     * @var $state_data
     */
    public $state_data;
    /**
     * @var MigrationState
     */
    public $migration_state;
    /**
     * @var Http
     */
    private $http;
    /**
     * @var DynamicProperties
     */
    private $dynamic_props;
    /**
     * @var MigrationState
     */
    private $migration_state_class;

    public function __construct(
        ErrorLog $error_log,
        Util $util,
        MigrationState $migration_state,
        Http $http,
        Properties $properties,
        StateDataContainer $state_data_container
    ) {
        $this->error_log             = $error_log;
        $this->props                 = $properties;
        $this->util                  = $util;
        $this->state_container       = $state_data_container;
        $this->dynamic_props         = DynamicProperties::getInstance();
        $this->state_data            = $this->state_container->state_data;
        $this->migration_state_class = $migration_state;
        $this->http                  = $http;
    }

    public function get_state_data()
    {
        return $this->state_data;
    }

    /**
     * Save the migration state, and replace the current item to be returned if there is an error.
     *
     * @param mixed       $state
     * @param mixed       $default The default value to return on success, optional defaults to null.
     * @param null|string $migration_id
     *
     * @return mixed|WP_Error
     */
    public function save_migration_state($state, $default = null, $migration_id = null)
    {
        if ( ! $this->migration_state->set($state, $migration_id)) {
            $error_msg = __('Failed to save migration state. Please contact support.', 'wp-migrate-db');
            $this->error_log->log_error($error_msg);

            return new WP_Error('wpmdb_error', $error_msg);
        }

        return $default;
    }

    public function get_state()
    {
        $migration_id = get_site_transient(WPMDB_MIGRATION_ID_TRANSIENT);

        if (empty($migration_id)) {
            return false;
        }

        return $this->migration_state->get($migration_id);
    }

    /**
     *  Restore previous migration state and merge in new information or initialize new migration state.
     *
     * @param null|string $id
     *
     * @return mixed|WP_Error
     */
    public function get_migration_state($id = null)
    {
        $return = true;

        if ( ! empty($id)) {
            $this->migration_state = new MigrationState($id);
            $state                 = $this->migration_state->get();
            if (empty($state) || $this->migration_state->id() !== $id) {
                $error_msg = __('Failed to retrieve migration state. Please contact support.', 'wp-migrate-db');
                $this->error_log->log_error($error_msg);

                return new WP_Error('wpmdb_error', $error_msg);
            } else {
                $this->state_data = array_merge($state, $this->state_data);

                $return = $this->save_migration_state($this->state_data, true);
            }
        } else {
            $this->migration_state = new MigrationState();
        }

        return $return;
    }

    /**
     * Sets $this->state_data from $_POST, potentially un-slashed and un-sanitized.
     *
     * @param array  $key_rules An optional associative array of expected keys and their sanitization rule(s).
     * @param string $state_key The key in $_POST that contains the migration state id (defaults to 'migration_state_id').
     * @param string $context   The method that is specifying the sanitization rules. Defaults to calling method.
     *
     * @return mixed|WP_Error
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function set_post_data($key_rules = [], $state_key = 'migration_state_id', $context = '')
    {
        if (empty($key_rules)) {
            return Persistence::getStateData();
        }

        if (defined('DOING_WPMDB_TESTS')) {
            // @TODO this is a major hack and should be refactored
            $this->state_data = $_POST;
        } elseif (empty($this->state_data)) {
            $this->state_data = Util::safe_wp_unslash($_POST);
        } else {
            return $this->state_data;
        }

        // From this point on we're handling data originating from $_POST, so original $key_rules apply.
        $context   = empty($context) ? $this->util->get_caller_function() : trim($context);
        $sanitized = Sanitize::sanitize_data($this->state_data, $key_rules, $context);

        if (is_wp_error($sanitized)) {
            $this->error_log->log_error($sanitized->get_error_message());
            error_log($sanitized->get_error_message());

            return $sanitized;
        }

        $this->state_data = $sanitized;

        if (false === $this->state_data) {
            exit;
        }

        $migration_state_id = null;
        if ( ! empty($this->state_data[$state_key])) {
            $migration_state_id = $this->state_data[$state_key];
        }

        // Always pass migration_state_id or $state_key with every AJAX request.
        $result = $this->get_migration_state($migration_state_id);

        if (is_wp_error($result)) {
            return $result;
        }

        WPMDBDI::getInstance()->get(StateDataContainer::class)->setData($this->state_data);

        return $this->state_data;
    }
}
