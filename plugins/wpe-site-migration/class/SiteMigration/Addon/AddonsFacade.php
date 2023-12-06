<?php

namespace DeliciousBrains\WPMDB\SiteMigration\Addon;

class AddonsFacade extends \DeliciousBrains\WPMDB\Common\Addon\AddonsFacade
{


    /**
     * @param array $addons
     */
    public function __construct(array $addons = [])
    {
        parent::__construct($addons);
    }

    public function register()
    {
        parent::register();
    }

    /**
     * Initializes registered addons
     *
     * @return void
     */
    public function initialize_addons()
    {
        $addons_list = [
            'wp-migrate-db-pro-media-files',
            'wp-migrate-db-pro-theme-plugin-files'
        ];
        if (is_array($addons_list)) {
            foreach ($this->addons as $addon) {
                $addon->register(true);
            }
        }
    }
}
