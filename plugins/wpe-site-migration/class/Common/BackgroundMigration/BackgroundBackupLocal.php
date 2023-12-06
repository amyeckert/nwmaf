<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

class BackgroundBackupLocal extends BackgroundMigration
{
    /**
     * @inheritdoc
     */
    protected static $type = 'backup_local';

    /**
     * @inheritDoc
     */
    protected function get_background_process_class()
    {
        return new BackgroundBackupLocalProcess($this);
    }
}
