<?php

namespace DeliciousBrains\WPMDB\SiteMigration;

use DeliciousBrains\WPMDB\Common\EntitlementsInterface;

class License implements EntitlementsInterface
{
    public function register()
    {
    }

    /**
     * Checks whether the saved licence has expired or not.
     *
     * For WP Engine Site Migration this is always true.
     *
     * @param bool $skip_transient_check
     *
     * @return bool
     */
    public function is_valid_licence($skip_transient_check = false)
    {
        return true;
    }

    /**
     * Returns the saved licence.
     *
     * For WP Engine Site Migration this is always empty.
     *
     * @return string
     */
    public function get_licence_key()
    {
        return '';
    }
}
