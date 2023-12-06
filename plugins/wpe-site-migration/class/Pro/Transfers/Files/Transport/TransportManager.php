<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport;

use DeliciousBrains\WPMDB\Common\Exceptions\UnknownStateProperty;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\CurrentMigrationState;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;
use WP_Error;

class TransportManager
{
    const TRANSPORT_ERROR_COUNT_PROPERTY = 'file_transport_error_count';
    const TRANSPORT_METHOD_PROPERTY      = 'file_transport_method';
    const FALLBACK_RETRY_COUNT           = 2;

    /**
     * @var TransportInterface
     */
    private $default_transport;

    /**
     * @var TransportInterface
     */
    private $fallback_transport;

    /**
     * A list of errors that are used to trigger the transport method fallback routine.
     *
     * @var string[]
     */
    private $fallback_errors = [
        'cURL error 28',
    ];

    /**
     * Transports a file to destination using one of the set transport methods.
     *
     * @param resource    $payload_file
     * @param array       $request_data
     * @param string|null $url
     *
     * @return FileTransportResponse|WP_Error
     */
    public function transport($payload_file, $request_data = [], $url = null)
    {
        $method = $this->get_transport_method();

        if (is_wp_error($method)) {
            return $method;
        }

        $response = $method->transport($payload_file, $request_data, $url);

        if (is_wp_error($response)) {
            $this->handle_error($response);
        }

        return $response;
    }

    /**
     * Handles the errors returned from file transport attempts
     * and updates the current_migration state with the error count to be used
     * to determine the transport method in subsequent requests.
     *
     * @param WP_Error $error
     *
     * @return void
     */
    private function handle_error($error)
    {
        $error_message   = $error->get_error_message();
        $fallback_errors = array_filter($this->fallback_errors, function ($item) use ($error_message) {
            return strpos(trim(strtolower($error_message)), trim(strtolower($item))) !== false;
        });

        if (count($fallback_errors) > 0) {
            $current_migration = StateFactory::create('current_migration')->load_state(null);

            if (is_a($current_migration, CurrentMigrationState::class)) {
                try {
                    $count = (int)$current_migration->get(self::TRANSPORT_ERROR_COUNT_PROPERTY);
                    $current_migration->set(self::TRANSPORT_ERROR_COUNT_PROPERTY, $count + 1, false);
                } catch (UnknownStateProperty $exception) {
                    $current_migration->set(self::TRANSPORT_ERROR_COUNT_PROPERTY, 1, false);
                }

                $current_migration->update_state();
            }
        }
    }

    /**
     * Returns the transport method that should be used for transport.
     *
     * @return TransportInterface|WP_Error
     */
    public function get_transport_method()
    {
        if ($this->should_use_fallback_method()) {
            $method = $this->fallback_transport;
        } else {
            $method = $this->default_transport;
        }

        $current_migration = StateFactory::create('current_migration')->load_state(null);

        if (is_a($current_migration, CurrentMigrationState::class)) {
            $current_migration->set(self::TRANSPORT_METHOD_PROPERTY, $method->get_method_name(), false);
            $current_migration->update_state();
        }

        if (empty($method)) {
            return new WP_Error(
                'wpmdb_transport_manager',
                __('Unable to determine transport method, method is empty.', 'wp-migrate-db')
            );
        }

        return $method;
    }

    /**
     * Determines if the fallback method should be used.
     *
     * @return bool
     */
    public function should_use_fallback_method()
    {
        if (empty($this->fallback_transport)) {
            return false;
        }

        $current_migration = StateFactory::create('current_migration')->load_state(null);

        if (is_a($current_migration, CurrentMigrationState::class)) {
            try {
                $count = $current_migration->get(self::TRANSPORT_ERROR_COUNT_PROPERTY);
                if ($count >= self::FALLBACK_RETRY_COUNT) {
                    return true;
                } else {
                    return false;
                }
            } catch (UnknownStateProperty $exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param string $method Transport method class name.
     *
     * @return void
     */
    public function set_default_method($method)
    {
        $this->default_transport = TransportFactory::create($method);
    }

    /**
     * @param string $method Transport method class name.
     *
     * @return void
     */
    public function set_fallback_method($method)
    {
        $this->fallback_transport = TransportFactory::create($method);
    }
}
