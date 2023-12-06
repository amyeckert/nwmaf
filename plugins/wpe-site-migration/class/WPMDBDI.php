<?php

namespace DeliciousBrains\WPMDB;

use DeliciousBrains\WPMDB\Container\DI;

class WPMDBDI
{

    private static $container;

    public static function getInstance()
    {
        if (self::$container instanceof DI\Container) {
            return self::$container;
        }

        $containerBuilder = new DI\ContainerBuilder;
        $containerBuilder->addDefinitions(__DIR__ . '/WPMDBDI_Config.php');
        $containerBuilder->useAutowiring(false);
        self::$container = $containerBuilder->build();

        return self::$container;
    }
}
