<?php

namespace DeliciousBrains\WPMDB\SiteMigration;

use DeliciousBrains\WPMDB\WPMigrateDB;

class WPESiteMigration extends WPMigrateDB
{
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * Register WordPress hooks here
     */
    public function register()
    {
        parent::register();
        $register_wpe = new RegisterWPE();
        $register_wpe->register();

        add_filter('wpmdb_initiate_key_rules', array($this, 'filter_key_rules'), 10, 2);
    }

    /**
     * Adds site_migration array to state data.
     *
     * @param array $rules
     * @param string $context
     *
     * @return array
     */
    public function filter_key_rules($rules, $context)
    {
        $rules['site_migration'] = 'array';

        return $rules;
    }
}
