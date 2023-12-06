<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

interface ApplicationStateRepositoryInterface
{
    public function get($state_branch = null, $migration_id = null);

    public function update($data, $state_branch = null, $migration_id = null);
}
