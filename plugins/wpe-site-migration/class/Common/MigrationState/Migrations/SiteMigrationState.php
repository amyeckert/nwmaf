<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Migrations;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class SiteMigrationState extends ApplicationStateAbstract
{
    protected $state_identifier = 'site_migration';

    /**
     * Get the initial state of the current migration state branch.
     *
     * @return array
     */
    public function get_initial_state()
    {
        return [
            "migrationOption"   => '',
            "notificationEmail" => '',
        ];
    }
}
