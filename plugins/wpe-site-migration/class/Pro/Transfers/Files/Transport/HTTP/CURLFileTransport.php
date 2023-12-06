<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\HTTP;

use DeliciousBrains\WPMDB\Common\MigrationPersistence\Persistence;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\FileTransportResponse;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\TransportInterface;
use CURLFile;
use Requests;
use Requests_Hooks;
use WP_Error;
use Exception;

class CURLFileTransport implements TransportInterface
{

    /**
     * Human-readable method name.
     *
     * @var string
     */
    private $name = 'CURLFileTransport';

    /**
     * @var Util
     */
    private $util;

    /**
     * @param Util $util
     */
    public function __construct(Util $util)
    {
        $this->util = $util;
    }

    /**
     * @inheritDoc
     */
    public function register()
    {
    }

    /**
     * @inheritDoc
     */
    public function transport($file, $request_payload = [], $url = null)
    {
        $options = apply_filters('wpmdb_transfers_requests_options', $this->util->get_requests_options());

        if (empty($url)) {
            return new WP_Error(
                'wpmdb_curl_transport',
                __('File transport URL is empty.', 'wp-migrate-db')
            );
        }

        if ( ! is_array($options)) {
            return new WP_Error(
                'wpmdb_curl_transport',
                __('File transport request options is not a valid array.', 'wp-migrate-db')
            );
        }

        if (empty($file)) {
            return new WP_Error(
                'wpmdb_file_transport_error',
                __('Could not transport payload, no payload provided.', 'wp-migrate-db')
            );
        }

        if ( ! is_resource($file)) {
            return new WP_Error(
                'wpmdb_file_transport_error',
                __('Provided file is not a valid resource.', 'wp-migrate-db')
            );
        }

        // Prepare to send file as a stream
        $meta_data = stream_get_meta_data($file);
        $filename  = $meta_data['uri'];

        // Verify the resource uri isn't empty
        if (empty($filename)) {
            return new WP_Error(
                'wpmdb_curl_transport',
                __('Resource URI is empty.', 'wp-migrate-db')
            );
        }

        // Attach the payload to the request as an octet stream to be received in $_FILES
        $request_payload['payload']          = new CURLFile($filename, 'application/octet-stream', 'payload');
        $request_payload['transport_method'] = self::class;

        $hooks = new Requests_Hooks();

        $hooks->register('curl.before_send', function ($handle) use ($request_payload) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $request_payload);
        });

        $options['hooks'] = $hooks;

        // Set WPE Cookie if it exists
        $remote_cookie = Persistence::getRemoteWPECookie();
        if (false !== $remote_cookie) {
            $options['cookies'] = [
                'wpe-auth' => $remote_cookie,
            ];
        }

        try {
            $response = Requests::post($url, array(), null, $options);

            return new FileTransportResponse($response->body, $response->status_code);
        } catch (Exception $error) {
            return new WP_Error('wpmdb_curl_transport', $error->getMessage());
        }
    }

    /**
     * @inheritDoc
     */
    public function receive()
    {
        if (empty($_FILES['payload']['tmp_name'])) {
            return new WP_Error(
                'wpmdb_curl_transport_receiver',
                __('File transport payload is empty', 'wp-migrate-db')
            );
        }

        $tmp_file = $_FILES['payload']['tmp_name'];

        return fopen($tmp_file, 'rb');
    }

    /**
     * @inheritDoc
     */
    public function get_method_name()
    {
        return $this->name;
    }
}
