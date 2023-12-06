<?php

namespace DeliciousBrains\WPMDB\Common\MigrationState;

interface ApplicationStateInterface
{
    public function get_initial_state();

    public function load_state($migration_id);

    public function set($property, $value);

    public function get($property);

    public function get_state();

    public function update_state($properties = []);
}
