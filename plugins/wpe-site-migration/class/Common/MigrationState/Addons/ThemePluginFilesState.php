<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState\Addons;

use DeliciousBrains\WPMDB\Common\MigrationState\ApplicationStateAbstract;

class ThemePluginFilesState extends ApplicationStateAbstract
{
    protected $state_identifier = "theme_plugin_files";

    /**
     * Set a property in the state
     *
     * @param string $property Property to set
     * @param mixed  $value    Value to set
     *
     * @return void
     */
    public function set($property, $value, $safe = true)
    {
        parent::set($property, $value, false);
    }

    /**
     * Bulk update the state
     *
     * @param array $properties Properties to update, key => value
     *
     * @return void
     */
    public function update_state($properties = [])
    {

        parent::update_state($properties);
    }

}
