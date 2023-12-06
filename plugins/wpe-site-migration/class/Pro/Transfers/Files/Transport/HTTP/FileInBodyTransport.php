<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\HTTP;

use DeliciousBrains\WPMDB\Common\Http\RemotePost;
use DeliciousBrains\WPMDB\Common\Transfers\Files\Util;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\FileTransportResponse;
use DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport\TransportInterface;
use WP_Error;

class FileInBodyTransport implements TransportInterface
{

    /**
     * Human-readable method name.
     *
     * @var string
     */
    private $name = 'FileInBodyTransport';

    /**
     * @var RemotePost
     */
    private $remote_post;

    /**
     * @param RemotePost $remote_post
     */
    public function __construct(RemotePost $remote_post)
    {
        $this->remote_post = $remote_post;
    }

    /**
     * @inheritdoc
     */
    public function register()
    {
        add_filter('wpmdb_high_performance_transfers_bottleneck',
            [$this, 'filter_high_performance_transfers_bottleneck']);
    }

    /**
     * @inheritDoc
     */
    public function transport($file, $request_payload = [], $url = null)
    {
        if (empty($url)) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('File transport URL is empty.', 'wp-migrate-db')
            );
        }

        if (empty($file)) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('Could not transport payload, no payload provided.', 'wp-migrate-db')
            );
        }

        if ( ! is_resource($file)) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('Provided file is not a valid resource.', 'wp-migrate-db')
            );
        }

        if ( ! rewind($file)) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('Unable to rewind the payload file.', 'wp-migrate-db')
            );
        }

        $file_contents = stream_get_contents($file);

        if (false === $file_contents) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('Unable to get stream contents.', 'wp-migrate-db')
            );
        }

        $file_contents = gzencode($file_contents);

        if (false === $file_contents) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('Unable to get gzencode file contents.', 'wp-migrate-db')
            );
        }

        $file_contents = base64_encode($file_contents);

        $request_payload['payload']          = &$file_contents;
        $request_payload['transport_method'] = self::class;

        $response = $this->remote_post->post($url, $request_payload, __METHOD__);

        if (empty($response)) {
            return new WP_Error(
                'wpmdb_inbody_transport',
                __('Unable to transport the payload file to the destination.', 'wp-migrate-db')
            );
        }

        if (is_wp_error($response)) {
            return $response;
        }

        return new FileTransportResponse($response, 200);
    }

    /**
     * @inheritdoc
     */
    public function receive()
    {
        if (empty($_POST['payload'])) {
            return null;
        }

        $tmp_file = Util::tmpfile();

        if (empty($tmp_file)) {
            return new WP_Error(
                'wpmdb_inbody_transport_receiver',
                __('Unable to create temporary file to receive payload.', 'wp-migrate-db')
            );
        }

        $payload_content = base64_decode($_POST['payload']);

        if (false === $payload_content) {
            return new WP_Error(
                'wpmdb_inbody_transport_receiver',
                __('Unable to decode payload contents.', 'wp-migrate-db')
            );
        }

        $payload_content = gzdecode($payload_content);

        if (false === $payload_content) {
            return new WP_Error(
                'wpmdb_inbody_transport_receiver',
                __('Unable to decompress payload contents.', 'wp-migrate-db')
            );
        }

        if (false === fwrite($tmp_file, $payload_content)) {
            return new WP_Error(
                'wpmdb_inbody_transport_receiver',
                __('Unable to write payload contents to a tmp file.', 'wp-migrate-db')
            );
        }

        if (false === rewind($tmp_file)) {
            return new WP_Error(
                'wpmdb_inbody_transport_receiver',
                __('Unable to rewind payload tmp file.', 'wp-migrate-db')
            );
        }

        unset($_POST['payload']);

        return $tmp_file;
    }

    /**
     * Filters the file transfers payload bottleneck value.
     *
     * @param int $bottleneck
     *
     * @return int
     * @handles wpmdb_high_performance_transfers_bottleneck
     */
    public function filter_high_performance_transfers_bottleneck($bottleneck)
    {
        // Fix payload size to 1mb and don't use any ramp up algorithms for more reliability.
        return 1000000;
    }

    /**
     * @inheritDoc
     */
    public function get_method_name()
    {
        return $this->name;
    }
}
