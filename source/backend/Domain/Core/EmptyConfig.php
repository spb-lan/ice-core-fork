<?php

namespace Ifacesoft\Ice\Core\Domain\Core;

use Ifacesoft\Ice\Core\Domain\Singleton;

final class EmptyConfig extends Config implements Singleton
{
    private static $emptyConfig;

    public static function create($data = [])
    {
        if (self::$emptyConfig) {
            return self::$emptyConfig;
        }

        return self::$emptyConfig = parent::create();
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }
}
