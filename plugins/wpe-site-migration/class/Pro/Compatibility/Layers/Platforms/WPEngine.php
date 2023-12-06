<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms;

use DeliciousBrains\WPMDB\Common\Util\Util;

class WPEngine extends AbstractPlatform
{
    /**
     * @var string
     */
    protected static $key = 'wp_engine';

    public function __construct()
    {
        parent::__construct();

        add_filter('wpmdb_get_connection_info', [$this, 'filter_get_connection_info']);
    }

    /**
     * Filters the current platform key.
     *
     * @param string $platform
     *
     * @return string
     */
    public function filter_platform($platform)
    {
        $wpe_cookie = Util::get_wpe_cookie();

        if ( ! empty($wpe_cookie)) {
            return static::get_key();
        }

        return $platform;
    }

    /**
     * Filter the connection info to substitute in the canonical URL for a WP Engine site.
     *
     * @param array $connection_info
     *
     * @return array
     *
     * @handles wpmdb_get_connection_info
     */
    public function filter_get_connection_info($connection_info)
    {
        if ( ! is_array($connection_info) || empty($connection_info)) {
            return $connection_info;
        }

        if ( ! defined('PWP_NAME') || empty(PWP_NAME)) {
            return $connection_info;
        }

        $connection_info[0] = 'https://' . PWP_NAME . '.wpengine.com';

        return $connection_info;
    }
}
