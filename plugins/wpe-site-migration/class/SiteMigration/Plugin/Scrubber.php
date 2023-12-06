<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Plugin;

use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationManager;

class Scrubber
{
    /**
     * The amount of time to wait to delete migration data
     */
    const DELETION_DELAY = DAY_IN_SECONDS * 7;

    /**
     * The identifier used for the cron to delete migration data
     */
    const DELAYED_DELETION_CRON_IDENTIFIER = WPMDB_OPTION_PREFIX . 'delayed_delete_migration';

    /**
     *  @var array
     **/
    protected $migration_options = [
        WPMDB_RECENT_MIGRATIONS_OPTION,
        WPMDB_MIGRATION_OPTIONS_OPTION,
        WPMDB_MIGRATION_STATE_OPTION,
        WPMDB_MIGRATION_STATS_OPTION,
    ];

    /**
     * Register hooks
     **/
    public function register()
    {
        add_action('wpmdb_migration_dismissed', [$this, 'migration_dismissed']);
        add_action('wpmdb_deactivate_plugin', [$this, 'deactivate_plugin']);
        add_action('wpmdb_migration_started', [$this, 'migration_started']);
        add_action(self::DELAYED_DELETION_CRON_IDENTIFIER, [$this, 'delayed_deletion']);
    }

    /**
     * When migration is dismissed
     *
     * @handles wpmdb_migration_dismissed
     * @return void
     **/
    public function migration_dismissed()
    {
        $this->delete_migration_options();
    }

    /**
     * Runs when plugin is deactivated
     *
     * Hooked to plugin deactivation hook
     *
     * @return void
     **/
    public function deactivate_plugin()
    {
        $this->delete_migration_options();
        self::delete_usermeta();
    }

    /**
     * Deletes migration options
     *
     * @return void
     **/
    protected function delete_migration_options()
    {
        foreach ($this->migration_options as $option) {
            delete_site_option($option);
        }
        $this->remove_delayed_deletion();
    }

    /**
     * Deletes plugin options
     *
     * Called from the plugin deletion hook
     * Uses wildcard to remove transients and options related to the plugin
     *
     * @return mixed int|bool
     **/
    public static function delete_all_plugin_options()
    {
        global $wpdb;
        $like = '%' . $wpdb->esc_like('wpesm_') . '%';
        $sql  = $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
            $like
        );

        return $wpdb->query($sql);
    }

    /**
     * Deletes usermeta migration data
     *
     * @return mixed int|bool
     **/
    public static function delete_usermeta()
    {
        global $wpdb;
        $identifier = BackgroundMigrationManager::LAST_MIGRATION_USERMETA_IDENTIFIER;
        $sql        = $wpdb->prepare(
            "DELETE FROM $wpdb->usermeta WHERE meta_key=%s",
            $identifier
        );

        return $wpdb->query($sql);
    }

    /**
     * Handles cron when migration starts
     *
     * @param string $migration_id
     *
     * @handles wpmdb_migration_started
     * @return void
     **/
    public function migration_started($migration_id)
    {
        //remove any previous cron
        $this->remove_delayed_deletion();
        //setup new cron
        $this->create_delayed_deletion();
    }

    /**
     * Called by the cron
     *
     * @handles wpesm_delayed_delete_migration
     * @return void
     **/
    public function delayed_deletion()
    {
        $this->delete_migration_options();
        self::delete_usermeta();
    }

    /**
     * Creates cron event to delete migration options
     *
     * Cron is set to run 72 hours after migration begins
     * @return void
     **/
    public function create_delayed_deletion()
    {
        wp_schedule_single_event(
            time() + self::DELETION_DELAY,
            self::DELAYED_DELETION_CRON_IDENTIFIER
        );
    }

    /**
     * Removes cron
     **/
    public function remove_delayed_deletion()
    {
        wp_clear_scheduled_hook(self::DELAYED_DELETION_CRON_IDENTIFIER);
    }
}
