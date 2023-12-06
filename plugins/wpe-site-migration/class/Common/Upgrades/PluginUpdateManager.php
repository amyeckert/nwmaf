<?php

namespace DeliciousBrains\WPMDB\Common\Upgrades;

use DeliciousBrains\WPMDB\Common\Properties\Properties;
use stdClass;
use WP_Error;

class PluginUpdateManager
{
    /**
     * Default WP Product Info API base
     */
    const UPDATE_BASE = 'https://wp-product-info.wpesvc.net/v1/plugins';
    /**
     * API Cache Time
     */
    const CACHE_TIME = HOUR_IN_SECONDS * 12;
    /**
     * @var Properties
     */
    private $properties;

    /**
     * @param Properties $properties
     */
    public function __construct(Properties $properties)
    {
        $this->properties = $properties;
    }

    /**
     * Register hooks.
     *
     * @return void
     */
    public function register()
    {
        add_filter('plugins_api', [$this, 'filter_plugin_update_info'], 20, 3);
        add_filter('site_transient_update_plugins', [$this, 'filter_plugin_update_transient']);
    }

    /**
     * Filter the plugin update transient to take over update notifications.
     *
     * @param object $transient
     *
     * @handles site_transient_update_plugins
     * @return object
     */
    public function filter_plugin_update_transient($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $result = $this->fetch_plugin_info();

        if (false === $result) {
            return $transient;
        }

        if (version_compare($this->properties->plugin_version, $result->version, '<')) {
            $res                               = $this->parse_plugin_info($result);
            $transient->response[$res->plugin] = $res;
            $transient->checked[$res->plugin]  = $result->version;
        }

        return $transient;
    }

    /**
     * Filters the plugin update information.
     *
     * @param object $res
     * @param string $action
     * @param object $args
     *
     * @handles plugins_api
     * @return object
     */
    public function filter_plugin_update_info($res, $action, $args)
    {
        // do nothing if this is not about getting plugin information
        if ('plugin_information' !== $action) {
            return $res;
        }

        // do nothing if it is not our plugin
        if ($this->properties->plugin_slug !== $args->slug) {
            return $res;
        }

        $result = $this->fetch_plugin_info();

        // do nothing if we don't get the correct response from the server
        if (false === $result) {
            return $res;
        }

        return $this->parse_plugin_info($result);
    }

    /**
     * Fetches the plugin update object from the WP Product Info API.
     *
     * @return object|false
     */
    private function fetch_plugin_info()
    {
        //Fetch cache first
        $response = get_transient(WPMDB_PRODUCT_INFO_RESPONSE_TRANSIENT);

        if (empty($response)) {
            $response = wp_remote_get(
                sprintf('%s/%s', self::UPDATE_BASE, $this->properties->plugin_slug),
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json',
                    ),
                )
            );

            if (is_wp_error($response)
                || 200 !== wp_remote_retrieve_response_code($response)
                || empty(wp_remote_retrieve_body($response)
                )) {
                return false;
            }

            $response = wp_remote_retrieve_body($response);

            //Cache the response
            set_transient(WPMDB_PRODUCT_INFO_RESPONSE_TRANSIENT, $response, self::CACHE_TIME);
        }

        return json_decode($response);
    }

    /**
     * Parses the product info response into an object that WordPress would be able to understand.
     *
     * @param object $response
     *
     * @return stdClass
     */
    private function parse_plugin_info($response)
    {
        global $wp_version;

        $res                = new stdClass();
        $res->name          = $response->name;
        $res->slug          = $response->slug;
        $res->version       = $response->version;
        $res->requires      = $response->requires_at_least;
        $res->download_link = $response->download_link;
        $res->trunk         = $response->download_link;
        $res->new_version   = $response->version;
        $res->plugin        = $this->properties->plugin_basename;
        $res->package       = $response->download_link;

        // Plugin information modal and core update table use a strict version comparison, which is weird.
        // If we're genuinely not compatible with the point release, use our WP tested up to version,
        // otherwise use exact same version as WP to avoid false positive.
        $res->tested = 1 === version_compare(substr($wp_version, 0, 3), $response->tested_up_to)
            ? $response->tested_up_to : $wp_version;

        $res->sections = array(
            'description' => $response->sections->description,
            'changelog'   => $response->sections->changelog,
        );

        return $res;
    }
}
