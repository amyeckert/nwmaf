<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files;

use DeliciousBrains\WPMDB\Common\Exceptions\UnknownTransportMethod;
use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\Http\Helper;
use DeliciousBrains\WPMDB\Common\Http\Http;
use DeliciousBrains\WPMDB\Common\Http\Scramble;
use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\MigrationState\MigrationStateManager;
use DeliciousBrains\WPMDB\Common\MigrationState\StateDataContainer;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Transfers\Files\FileProcessor;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Common\Queue\Manager;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\TransportFactory;
use DeliciousBrains\WPMDB\Pro\Transfers\Receiver;
use DeliciousBrains\WPMDB\Pro\Transfers\Sender;
use Exception;
use WP_Error;

class PluginHelper extends \DeliciousBrains\WPMDB\Common\Transfers\Files\PluginHelper
{

    /**
     * @var Sender
     */
    protected $sender;
    /**
     * @var Receiver
     */
    protected $receiver;

    public function __construct(
        Filesystem $filesystem,
        Properties $properties,
        Http $http,
        Helper $http_helper,
        Settings $settings,
        MigrationStateManager $migration_state_manager,
        Scramble $scramble,
        FileProcessor $file_processor,
        Util $transfer_util,
        Manager $queue_manager,
        Manager $manager,
        StateDataContainer $state_data_container,
        Sender $sender,
        Receiver $receiver
    ) {
        parent::__construct(
            $filesystem,
            $properties,
            $http,
            $http_helper,
            $settings,
            $migration_state_manager,
            $scramble,
            $file_processor,
            $transfer_util,
            $queue_manager,
            $manager,
            $state_data_container
        );
        $this->sender   = $sender;
        $this->receiver = $receiver;
    }

    /**
     * Respond to post of a file.
     *
     * @return void
     * @throws Exception
     */
    public function respond_to_post_file()
    {
        $key_rules = array(
            'action'           => 'key',
            'remote_state_id'  => 'key',
            'stage'            => 'string',
            'intent'           => 'string',
            'folders'          => 'array',
            'theme_folders'    => 'array',
            'themes_option'    => 'string',
            'plugin_folders'   => 'array',
            'plugins_option'   => 'string',
            'muplugin_folders' => 'array',
            'muplugins_option' => 'string',
            'other_folders'    => 'array',
            'others_option'    => 'string',
            'sig'              => 'string',
        );

        if ( ! isset($_POST['state_data'])) {
            throw new Exception(__('Failed to respond to payload post, empty state data.', 'wp-migrate-db'));
        }

        $decoded_json_state = json_decode(base64_decode($_POST['state_data']), true);

        //Sending ALL local state data, probably too much data and should be paired down
        $state_data = Persistence::setRemotePostData(
            $key_rules,
            __METHOD__,
            WPMDB_REMOTE_MIGRATION_STATE_OPTION,
            $decoded_json_state
        );

        $filtered_post = $this->http_helper->filter_post_elements(
            $state_data,
            array(
                'action',
                'remote_state_id',
                'stage',
                'intent',
            )
        );

        $settings = $this->settings;

        if ( ! $this->http_helper->verify_signature($filtered_post, $settings['key'])) {
            $this->http->end_ajax(
                $this->transfer_util->log_and_return_error(
                    $this->properties->invalid_content_verification_error . ' (#100tp)', $filtered_post
                )
            );

            return;
        }

        if ( ! isset($_POST['transport_method'])) {
            $this->http->end_ajax(
                $this->transfer_util->log_and_return_error(
                    'Transport method is not included in file transfer request.', $filtered_post
                )
            );

            return;
        }

        $transport_method_class = sanitize_text_field($_POST['transport_method']);

        try {
            // Decode the transport method name and factory it.
            $transport_method = TransportFactory::create(
                str_replace(
                    "\\\\",
                    "\\",
                    $transport_method_class
                )
            );

            // Receive the payload
            $transported_file = $transport_method->receive();
        } catch (UnknownTransportMethod $exception) {
            $this->http->end_ajax(
                $this->transfer_util->log_and_return_error(
                    sprintf('Unknown transport method %s.', $transport_method_class),
                    $filtered_post
                )
            );

            return;
        }

        // If the received payload is an error or empty, log the error and return it.
        if (is_wp_error($transported_file)) {
            $this->http->end_ajax(
                $this->transfer_util->log_and_return_error(
                    $transported_file->get_error_message(), $filtered_post
                )
            );

            return;
        }

        if (empty($transported_file)) {
            $this->http->end_ajax(
                $this->transfer_util->log_and_return_error(
                    'Could not receive the transported file', $filtered_post
                )
            );

            return;
        }

        $receiver = $this->receiver;

        try {
            $result = $receiver->process_received_payload($state_data, $transported_file);
        } catch (Exception $e) {
            $this->http->end_ajax(new WP_Error('wpmdb_error', $e->getMessage()));

            return;
        }

        $this->http->end_ajax($result);
    }

    /**
     *
     * Fired off a nopriv AJAX hook that listens to pull requests for file batches
     *
     * @return void
     */
    public function respond_to_request_files()
    {
        $key_rules = array(
            'action'          => 'key',
            'remote_state_id' => 'key',
            'stage'           => 'string',
            'intent'          => 'string',
            'bottleneck'      => 'numeric',
            'sig'             => 'string',
        );

        $state_data = $this->migration_state_manager->set_post_data($key_rules, 'remote_state_id');

        if (is_wp_error($state_data)) {
            $this->http->end_ajax($state_data);

            return;
        }

        $filtered_post = $this->http_helper->filter_post_elements(
            $state_data,
            array(
                'action',
                'remote_state_id',
                'stage',
                'intent',
                'bottleneck',
            )
        );

        $settings = $this->settings;

        if ( ! $this->http_helper->verify_signature($filtered_post, $settings['key'])) {
            $this->http->end_ajax(
                $this->transfer_util->log_and_return_error(
                    $this->properties->invalid_content_verification_error . ' (#100tp)', $filtered_post)
            );

            return;
        }

        try {
            $this->sender->respond_to_send_file($state_data);
        } catch (Exception $e) {
            $this->http->end_ajax(new WP_Error('wpmdb_error', $e->getMessage()));
        }
    }
}
