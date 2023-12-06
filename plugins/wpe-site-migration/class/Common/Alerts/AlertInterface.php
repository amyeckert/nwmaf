<?php

namespace DeliciousBrains\WPMDB\Common\Alerts;

interface AlertInterface
{
    /**
     * Handles migrations started alerts.
     *
     * @param string $migration_id
     *
     * @return void
     */
    public function started($migration_id);

    /**
     * Handles migrations completed alerts.
     *
     * @param string $migration_id
     *
     * @return void
     */
    public function completed($migration_id);

    /**
     * Handles migrations failed alerts.
     *
     * @param string $migration_id
     *
     * @return void
     */
    public function failed($migration_id);

    /**
     * Handles migrations canceled alerts.
     *
     * @param string $migration_id
     *
     * @return void
     */
    public function canceled($migration_id);
}
