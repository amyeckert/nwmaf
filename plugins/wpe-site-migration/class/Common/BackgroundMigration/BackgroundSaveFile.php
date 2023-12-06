<?php

namespace DeliciousBrains\WPMDB\Common\BackgroundMigration;

class BackgroundSaveFile extends BackgroundMigration
{
    /**
     * @inheritdoc
     */
    protected static $type = 'savefile';

    /**
     * @inheritDoc
     */
    protected function get_background_process_class()
    {
        return new BackgroundSaveFileProcess($this);
    }
}
