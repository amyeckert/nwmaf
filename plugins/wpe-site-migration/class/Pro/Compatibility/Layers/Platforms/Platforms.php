<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms;

use WP_Error;

class Platforms
{
    /**
     * @var string[] List of classes implementing PlatformInterface
     */
    private $supported_platform = [WPEngine::class, Flywheel::class];

    /**
     * @var PlatformInterface[] Container of instantiated platform classes
     */
    private $platforms = [];

    /**
     * Platforms constructor.
     * Registers wpmdb_hosting_platform filter for each supported platform
     */
    public function __construct()
    {
        /** @var PlatformInterface $platform */
        foreach ($this->supported_platform as $platform) {
            $this->platforms[$platform::get_key()] = new $platform;
        }

        add_filter('wpmdb_site_details', [$this, 'filter_site_details']);
    }

    /**
     * Get the current platform's name if it is supported.
     *
     * @return string|null
     */
    public static function get_platform()
    {
        return apply_filters('wpmdb_hosting_platform', null);
    }

    /**
     * Filter site details to add platform.
     *
     * @param array|WP_Error $site_details
     *
     * @return array
     */
    public function filter_site_details($site_details)
    {
        if ( ! is_wp_error($site_details) && is_array($site_details)) {
            $site_details['platform'] = static::get_platform();
            $site_details['pwp_name'] = defined('PWP_NAME') ? PWP_NAME : '';
        }

        return $site_details;
    }
}
