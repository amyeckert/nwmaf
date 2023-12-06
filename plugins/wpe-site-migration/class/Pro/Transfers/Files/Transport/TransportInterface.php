<?php

namespace DeliciousBrains\WPMDB\Pro\Transfers\Files\Transport;

use WP_Error;

interface TransportInterface
{
    /**
     * Transports the file to the destination.
     *
     * @param resource    $file
     * @param array       $request_payload
     * @param string|null $url
     *
     * @return FileTransportResponse|WP_Error
     */
    public function transport($file, $request_payload = [], $url = null);

    /**
     * Receives the transported file on the destination.
     *
     * @return resource|WP_Error
     */
    public function receive();

    /**
     * Can be used to register hooks.
     *
     * @return void
     */
    public function register();

    /**
     * Returns the human-readable transport method name.
     *
     * @return string
     */
    public function get_method_name();
}
