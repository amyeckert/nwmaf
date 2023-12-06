<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms;

class Flywheel extends AbstractPlatform
{
    /**
     * @var string
     */
    protected static $key = 'flywheel';

    /**
     * Filters the current platform key.
     *
     * @param string $platform
     *
     * @return string
     */
    public function filter_platform($platform)
    {
        if (defined('FLYWHEEL_CONFIG_DIR')) {
            return static::get_key();
        }

        return $platform;
    }
}
