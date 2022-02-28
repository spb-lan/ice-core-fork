<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Throwable;

abstract class SingletonContainer extends Container implements Singleton
{
    /**
     * @var Container[]
     */
    private static $containers = [];

    /**
     * @param array $options
     * @param array $data
     * @param array $services
     * @return Container|Service
     * @throws Throwable
     */
    final public static function getInstance(array $options = [], array $data = [], array $services = [])
    {
        if (isset(self::$containers[static::class])) {
            return self::$containers[static::class];
        }

        return self::$containers[static::class] = parent::getInstance($options, $data, $services);
    }

    final public function __clone()
    {
    }

    final public function __wakeup()
    {
    }
}
