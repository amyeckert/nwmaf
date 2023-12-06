<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Plugin;

use DeliciousBrains\WPMDB\Common\Plugin\PluginManagerBase;
use DeliciousBrains\WPMDB\Common\Util\Util;
use WP_Error;

class PluginManager extends PluginManagerBase
{
    const UTM_PARAMS = [
        'utm_source'   => 'wpesm_plugin',
        'utm_medium'   => 'referral',
        'utm_campaign' => 'bx_prod_referral',
        'utm_content'  => 'wpesm_plugin_footer_text',
    ];

    public function register()
    {
        parent::register();
        add_filter('plugin_action_links_' . $this->props->plugin_basename, [$this, 'plugin_action_links']);
        add_filter(
            'network_admin_plugin_action_links_' . $this->props->plugin_basename,
            [$this, 'plugin_action_links']
        );
        add_filter(
            'wpmdb_handle_verify_connection_to_remote_site_response',
            [$this, 'filter_handle_verify_connection_to_remote_site_response'],
            10,
            2
        );
        add_filter('admin_footer_text', [$this, 'admin_footer_text']);
        add_filter('update_footer', [$this, 'update_footer'], 20);
    }

    /**
     * Adds additional links to plugin page
     *
     * @param array $links
     *
     * @return array $links
     */
    public function plugin_action_links($links)
    {
        $start_links = [
            'profiles' => sprintf(
                '<a href="%s">%s</a>',
                network_admin_url($this->props->plugin_base),
                __('Migrate', 'wp-migrate-db')
            ),
            'settings' => sprintf(
                '<a href="%s">%s</a>',
                network_admin_url($this->props->plugin_base) . '#settings',
                _x('Settings', 'Plugin configuration and preferences', 'wp-migrate-db')
            ),
        ];

        return $start_links + $links;
    }

    /**
     * Filter the response to the verify connection to remote site call.
     *
     * @param array $response from the remote
     * @param array $data     that was sent
     *
     * @returns array|WP_Error
     */
    public function filter_handle_verify_connection_to_remote_site_response($response, $data)
    {
        if (
            is_multisite() &&
            ! empty($response['site_details']['is_multisite']) &&
            'false' === $response['site_details']['is_multisite']
        ) {
            return new WP_Error(
                'multisite-to-single-site-install',
                sprintf(
                    __(
                        'It looks like you\'re trying to migrate a multisite to a single site, which isn\'t currently supported by this plugin. To continue, <a href="%s" target="_blank">convert the destination to a multisite</a> and try again.',
                        'wp-migrate-db'
                    ),
                    'https://wpengine.com/support/what-is-wordpress-multisite/#Convert_to_Multisite'
                )
            );
        }

        if (
            ! is_multisite() &&
            ! empty($response['site_details']['is_multisite']) &&
            'true' === $response['site_details']['is_multisite']
        ) {
            return new WP_Error(
                'multisite-to-single-site-install',
                sprintf(
                    __(
                        'It looks like you\'re trying to migrate a single site to a multisite, which isn\'t currently supported by this plugin. To continue, <a href="%s" target="_blank">convert the destination to a single site</a> and try again.',
                        'wp-migrate-db'
                    ),
                    'https://wpengine.com/support/what-is-wordpress-multisite/#Disable_Multisite'
                )
            );
        }

        return $response;
    }

    /**
     * Get the plugin version
     *
     * @return string
     **/
    public function get_plugin_version()
    {
        if (!isset($GLOBALS['wpmdb_meta']['wpe-site-migration']['version'])) {
            return '0';
        }

        return $GLOBALS['wpmdb_meta']['wpe-site-migration']['version'];
    }

    /**
     * Filter admin footer text for Migrate pages
     *
     * @param string $text
     * @return string
     * @handles admin_footer_text
     **/
    public function admin_footer_text($text)
    {
        if (!$this->util->isMDBPage()) {
            return $text;
        }
        $wpe_link     = Util::external_link(
            static::wpe_url(
                '',
                self::UTM_PARAMS
            ),
            'WP Engine'
        );

        return $this->generate_admin_footer_text($text, WPMDB_PLUGIN_TITLE, $wpe_link);
    }

    /**
     * Filter update footer text for Migrate pages
     *
     * @param string $content
     * @return string
     * @handles update_footer
     **/
    public function update_footer($content)
    {
        if (!$this->util->isMDBPage()) {
            return $content;
        }

        $links[] = Util::external_link(
            static::wpe_url(
                '/support/wp-engine-site-migration/',
                self::UTM_PARAMS
            ),
            __('Documentation', 'wp-migrate-db')
        );

        $links[] = Util::external_link(
            'https://my.wpengine.com/support/?wpesm&utm_source=wpesm_plugin&utm_medium=referral&utm_campaign=bx_prod_referral&utm_content=wpesm_plugin_footer_text',
            __('Support', 'wp-migrate-db')
        );

        $links[] = Util::external_link(
            'https://wpengine-product.canny.io/wpe-site-migration',
            __('Feedback', 'wp-migrate-db')
        );

        $links[] = WPMDB_PLUGIN_TITLE . ' ' . $this->get_plugin_version();

        return join(' &#8729; ', $links);
    }
}
