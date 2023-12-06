<?php

namespace DeliciousBrains\WPMDB\Common\Alerts\Email;

use DeliciousBrains\WPMDB\Common\Alerts\AbstractAlert;
use DeliciousBrains\WPMDB\Common\Alerts\AlertInterface;
use DeliciousBrains\WPMDB\Common\Exceptions\InvalidAlertTemplate;
use DeliciousBrains\WPMDB\Common\MigrationState\Migrations\RemoteSiteState;
use DeliciousBrains\WPMDB\Common\MigrationState\StateFactory;

class EmailAlert extends AbstractAlert
{
    /**
     * Gets the contents for the provided template.
     *
     * @param string $template_name
     *
     * @return string
     * @throws InvalidAlertTemplate
     */

    private function get_template($template_name)
    {
        $templates_path = __DIR__ . '/templates/' . $template_name . '.html';
        if (is_file($templates_path)) {
            return file_get_contents($templates_path);
        }

        throw new InvalidAlertTemplate(sprintf("Template %s doesn't exist.", $templates_path));
    }

    /**
     * Replaces placeholders in the templates' contents with their respective values.
     *
     * @param string $template_name
     * @param string $migration_id
     *
     * @return string
     */
    private function get_formatted_template($template_name, $migration_id)
    {
        $content = $this->get_template($template_name);

        $content = str_replace('{MIGRATION_ID}', $migration_id, $content);
        $content = str_replace('{DESTINATION_SITE_URL}', $this->get_destination_site_url($migration_id), $content);
        $content = str_replace('{URL_OF_THE_MIGRATE_TAB}', $this->get_source_migrate_url($migration_id), $content);
        $content = str_replace('{DESTINATION_LOGIN_URL}', $this->get_destination_login_url($migration_id), $content);
        $content = str_replace('{INSTALL_NAME}', $this->get_destination_install_name($migration_id), $content);
        $content = str_replace('{SOURCE_SITE_URL}', $this->get_source_site_url($migration_id), $content);


        return $content;
    }

    /**
     * Uses wp_mail to send and email to the user.
     *
     * @param string $migration_id
     * @param string $subject
     * @param string $content
     *
     * @return void
     */
    private function send_email($migration_id, $subject, $content)
    {
        $site_migration_state = StateFactory::create('site_migration')->load_state($migration_id);
        $user_email           = $site_migration_state->get('notificationEmail');
        $user_email           = sanitize_email($user_email);
        $headers              = array('Content-Type: text/html; charset=UTF-8');

        if ( ! empty($user_email)) {
            add_filter('wp_mail_from_name', array($this, 'set_from_name'));
            wp_mail($user_email, $subject, $content, $headers);
            remove_filter('wp_mail_from_name', array($this, 'set_from_name'));
        }
    }

    /**
     * Get destination site url
     *
     * @param string $migration_id
     *
     * @return string
     */
    private function get_destination_site_url($migration_id)
    {
        $remote_site_state = StateFactory::create('remote_site')->load_state($migration_id);

        return $remote_site_state->get('url');
    }

    /**
     * Get source site url
     *
     * @param string $migration_id
     *
     * @return string
     */
    private function get_source_site_url($migration_id)
    {
        $local_site_state = StateFactory::create('local_site')->load_state($migration_id);

        return $local_site_state->get('this_url');
    }

    /**
     * Get local site WP Migrate/WPESM url
     *
     * @param string $migration_id
     *
     * @return mixed
     */
    private function get_source_migrate_url($migration_id)
    {
        $site_state = StateFactory::create('local_site')->load_state($migration_id);

        return $site_state->get('site_details')['migrate_url'];
    }

    /**
     * Get destination site login url
     *
     * @param string $migration_id
     *
     * @return mixed
     */
    private function get_destination_login_url($migration_id)
    {
        $remote_site_state = StateFactory::create('remote_site')->load_state($migration_id);

        return $remote_site_state->get('site_details')['login_url'];
    }

    /**
     * @param string $migration_id
     *
     * @handles wpmdb_migration_started
     * @return  void
     */
    public function started($migration_id)
    {
        $content = $this->get_formatted_template('started', $migration_id);
        $subject = __('🚀 Site migration started', 'wp-migrate-db');

        $this->send_email($migration_id, $subject, $content);
    }

    /**
     * @param string $migration_id
     *
     * @handles wpmdb_migration_completed
     * @return  void
     */
    public function completed($migration_id)
    {
        $content = $this->get_formatted_template('completed', $migration_id);
        $subject = __('✅ Site migration completed', 'wp-migrate-db');

        $this->send_email($migration_id, $subject, $content);
    }

    /**
     * @param string $migration_id
     *
     * @handles wpmdb_migration_failed
     * @return  void
     */
    public function failed($migration_id)
    {
        $content = $this->get_formatted_template('failed', $migration_id);
        $subject = __('🚨 Site migration failed', 'wp-migrate-db');

        $this->send_email($migration_id, $subject, $content);
    }

    /**
     * @param string $migration_id
     *
     * @handles wpmdb_migration_canceled
     * @return  void
     */
    public function canceled($migration_id)
    {
        //Implement canceled email
    }

    /**
     * Returns the sender name.
     *
     * @param  string $from_name
     * @handles wp_mail_from_name
     * @return string
     */
    public function set_from_name($from_name)
    {
        return __('WP Engine Site Migration', 'wp-migrate-db');
    }

    /**
     * Returns the PWP Install name on WPE sites or an empty string on other platforms.
     *
     * @param string $migration_id
     *
     * @return string
     */
    public function get_destination_install_name($migration_id)
    {
        $remote_site_state = StateFactory::create('remote_site')->load_state($migration_id);
        $string_format     = '?wpesm_install=%s';
        if (
            is_a($remote_site_state, RemoteSiteState::class) &&
            is_array($remote_site_state->get('site_details')) &&
            ! empty($remote_site_state->get('site_details')['pwp_name'])
        ) {
            return sprintf($string_format, $remote_site_state->get('site_details')['pwp_name']);
        }

        return '';
    }
}
