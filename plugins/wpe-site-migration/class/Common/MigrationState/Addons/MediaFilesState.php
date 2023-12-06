<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Addons;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class MediaFilesState extends ApplicationStateAbstract
{
    protected $state_identifier = "media_files";

    public function set($property, $value, $safe = true)
    {
        parent::set($property, $value, false);
    }
}
