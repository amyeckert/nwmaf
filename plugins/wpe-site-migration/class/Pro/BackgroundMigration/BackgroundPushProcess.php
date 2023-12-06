<?php

namespace DeliciousBrains\WPMDB\Pro\BackgroundMigration;

use DeliciousBrains\WPMDB\Common\BackgroundMigration\BackgroundMigrationProcess;

class BackgroundPushProcess extends BackgroundMigrationProcess
{
    /**
     * @inheritdoc
     */
    protected $action = 'push';
}
