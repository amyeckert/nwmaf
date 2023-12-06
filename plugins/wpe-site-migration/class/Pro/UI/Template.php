<?php

namespace DeliciousBrains\WPMDB\Pro\UI;

use DeliciousBrains\WPMDB\Common\Filesystem\Filesystem;
use DeliciousBrains\WPMDB\Common\FormData\FormData;
use DeliciousBrains\WPMDB\Common\Profile\ProfileManager;
use DeliciousBrains\WPMDB\Common\Properties\DynamicProperties;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;
use DeliciousBrains\WPMDB\Common\Sql\Table;
use DeliciousBrains\WPMDB\Common\UI\Notice;
use DeliciousBrains\WPMDB\Common\UI\TemplateBase;
use DeliciousBrains\WPMDB\Common\Util\Util;

class Template extends TemplateBase
{

    /**
     * @var Notice
     */
    public $notice;
    /**
     * @var FormData
     */
    public $form_data;
    /**
     * @var DynamicProperties
     */
    public $dynamic_props;

    public function __construct(
        Settings $settings,
        Util $util,
        ProfileManager $profile,
        Filesystem $filesystem,
        Table $table,
        Notice $notice,
        FormData $form_data,
        Properties $properties
    ) {
        parent::__construct($settings, $util, $profile, $filesystem, $table, $properties);
        $this->notice    = $notice;
        $this->form_data = $form_data;

        $this->dynamic_props = DynamicProperties::getInstance();

        // Insert backups tab into plugin_tabs array
        array_splice($this->plugin_tabs, 1, 0, [
            [
                'slug'  => 'backups',
                'title' => _x('Backups', 'Get backups', 'wp-migrate-db'),
            ],
        ]);
    }

    public function register()
    {
        // templating actions
        add_filter('wpmdb_notification_strings', [$this, 'notifications']);
    }

    /**
     * Filter notifications to potentially notices for found problems.
     *
     * @param array $notifications
     *
     * @return array
     */
    public function notifications($notifications)
    {
        // Only show the warning if the key is 32 characters in length
        if ( ! empty($this->settings['allow_connection']) && strlen($this->settings['key']) <= 32) {
            $secret_key_notice_id = 'secret_key_warning';
            $secret_key_links     = $this->notice->check_notice($secret_key_notice_id, true, 604800);

            if (false !== $secret_key_links) {
                $msg = '<div>';
                $msg .= '<strong>' . __('Improve Security', 'wp-migrate-db') . '</strong> &mdash; ';
                $msg .= sprintf(
                    __(
                        'We have implemented a more secure method of secret key generation since your key was generated. We recommend you <a href="%s">visit the Settings tab</a> and reset your secret key.',
                        'wp-migrate-db'
                    ),
                    '#settings'
                );
                $msg .= '</div>';

                $notifications[$secret_key_notice_id] = [
                    'message' => $msg,
                    'link'    => $secret_key_links,
                    'id'      => $secret_key_notice_id,
                ];
            }
        }

        if (defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL) {
            $notice_id    = 'block_external_warning';
            $notice_links = $this->notice->check_notice($notice_id, true, 604800);

            if (false !== $notice_links) {
                $msg = '<div>';
                $msg .= sprintf(
                    __(
                        'We\'ve detected that <code>WP_HTTP_BLOCK_EXTERNAL</code> is enabled, which will prevent WP Migrate from functioning properly. You should either disable <code>WP_HTTP_BLOCK_EXTERNAL</code> or add any sites that you\'d like to migrate to or from with WP Migrate to <code>WP_ACCESSIBLE_HOSTS</code> (api.deliciousbrains.com must be added to <code>WP_ACCESSIBLE_HOSTS</code> for the API to work). More information on this can be found <a href="%s" target="_blank">here</a>.',
                        'wp-migrate-db'
                    ),
                    'https://deliciousbrains.com/wp-migrate-db-pro/doc/wp_http_block_external/?utm_campaign=error%2Bmessages&utm_source=MDB%2BPaid&utm_medium=insideplugin'
                );
                $msg .= '</div>';

                $notifications[$notice_id] = [
                    'message' => $msg,
                    'link'    => $notice_links,
                    'id'      => $notice_id,
                ];
            }
        }

        return $notifications;
    }
}
