<?php

namespace DeliciousBrains\WPMDB\Common\Alerts;

abstract class AbstractAlert implements AlertInterface
{
    /**
     * Register hooks
     *
     * @return void
     */
    public function register()
    {
        add_action('wpmdb_migration_started', [$this, 'started']);
        add_action('wpmdb_migration_canceled', [$this, 'canceled']);
        add_action('wpmdb_migration_failed', [$this, 'failed']);
        add_action('wpmdb_migration_completed', [$this, 'completed']);
    }
}
