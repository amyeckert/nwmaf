<?php

namespace DeliciousBrains\WPMDB\Pro\Compatibility\Layers\Platforms;

interface PlatformInterface
{
    public static function get_key();
    public function filter_platform($platform);
}
