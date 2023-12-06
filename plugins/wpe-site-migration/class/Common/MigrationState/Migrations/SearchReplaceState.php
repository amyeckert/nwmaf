<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Migrations;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class SearchReplaceState extends ApplicationStateAbstract
{
    protected $state_identifier = "search_replace";

    public function set($property, $value, $safe = true)
    {
        parent::set($property, $value, false);
    }
}
