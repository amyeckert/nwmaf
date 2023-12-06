<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Migrations;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class CurrentMigrationState extends ApplicationStateAbstract
{
    protected $state_identifier = 'current_migration';

    /**
     * Get the initial state of the current migration state branch.
     *
     * @return array
     */
    public function get_initial_state()
    {
        return [
            "connected"                     => false,
            "intent"                        => '',
            "tables_option"                 => 'all',
            "tables_selected"               => [],
            "backup_option"                 => 'none',
            "backup_tables_selected"        => [],
            "post_types_option"             => 'all',
            "post_types_selected"           => [],
            "advanced_options_selected"     => [],
            "profile_name"                  => '',
            "selected_existing_profile"     => null,
            "profile_type"                  => null,
            "status"                        => ["disabled" => false],
            "stages"                        => [],
            "current_stage"                 => '',
            "stages_complete"               => [],
            "running"                       => false,
            "migration_enabled"             => false,
            "migration_id"                  => null,
            "source_prefix"                 => '',
            "destination_prefix"            => '',
            "preview"                       => false,
            "selectedComboOption"           => 'preview',
            "twoMultisites"                 => false,
            "localSource"                   => true,
            "databaseEnabled"               => true,
            "currentPayloadSize"            => 0,
            "currentMaxPayloadSize"         => null,
            "fileTransferRequests"          => 0,
            "payloadSizeHistory"            => [],
            "fileTransferStats"             => [],
            "forceHighPerformanceTransfers" => true,
            "fseDumpFilename"               => null,
        ];
    }

    /**
     * Load current migration state from the database.
     *
     * @param string $migration_id
     *
     * @return $this
     */
    public function load_state($migration_id)
    {
        $state_data = $this->repository->get($this->state_identifier);

        if (empty($migration_id) && !empty($state_data)) {
            $this->state = $state_data;
        }

        if (isset($state_data['migration_id']) && $state_data['migration_id'] === $migration_id) {
            $this->state = $state_data;
        }

        return $this;
    }
}
