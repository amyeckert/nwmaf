<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Addon;

use DeliciousBrains\WPMDB\Common\Error\ErrorLog;
use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Common\Settings\Settings;

class Addon extends \DeliciousBrains\WPMDB\Common\Addon\Addon
{
    public function __construct(
        ErrorLog $log,
        Settings $settings,
        Properties $properties
    ) {
        parent::__construct($log, $settings, $properties);
    }
}
