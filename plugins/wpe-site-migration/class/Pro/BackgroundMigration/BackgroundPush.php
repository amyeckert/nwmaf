<?php

namespace DeliciousBrains\WPMDB\Pro\BackgroundMigration;

use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigration;

class BackgroundPush extends BackgroundMigration
{
    /**
     * @inheritdoc
     */
    protected static $type = 'push';

    /**
     * @inheritDoc
     */
    protected function get_background_process_class()
    {
        return new BackgroundPushProcess($this);
    }
}
