<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

class BackgroundBackupLocalProcess extends BackgroundMigrationProcess
{
    /**
     * @inheritdoc
     */
    protected $action = 'backup_local';

    /**
     * TODO: This is not needed for a local backup, it's here just for testing and setting the stage for other migration types.
     *
     * @inheritdoc
     */
    protected function stage_processed($progress, $stage, $item)
    {
        return parent::stage_processed($progress, $stage, $item);
    }
}
