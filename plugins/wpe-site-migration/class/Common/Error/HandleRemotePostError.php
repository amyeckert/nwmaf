<?php

namespace DeliciousBrains\WPMDB\Common\Error;

use WP_Error;

class HandleRemotePostError
{
    /**
     * Parse a remote post response for errors.
     *
     * If error found, return it, otherwise returns response.
     *
     * @param string $key
     * @param mixed  $response
     *
     * @return mixed|WP_Error
     */
    public static function handle($key, $response)
    {
        // WP_Error is thrown manually by remote_post() to tell us something went wrong.
        if (is_wp_error($response)) {
            return $response;
        }

        $decoded_response = json_decode($response, true);

        if (false === $response || ! $decoded_response['success']) {
            return new WP_Error(
                $key,
                $decoded_response['data']
            );
        }

        if (isset($decoded_response['data'])) {
            return $decoded_response['data'];
        }

        return $response;
    }
}
