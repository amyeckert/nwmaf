<?php

namespace DeliciousBrains\WPMDB\Common;

interface EntitlementsInterface
{
    public function register();

    /**
     * Checks whether the saved licence has expired or not.
     *
     * @param bool $skip_transient_check
     *
     * @return bool
     */
    public function is_valid_licence();

    /**
     * Returns the saved licence.
     *
     * @return string
     */
    public function get_licence_key();
}
