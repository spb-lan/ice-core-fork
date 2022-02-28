<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Exception;

abstract class SingletonService extends Service implements Singleton
{
    /**
     * @var Service[]
     */
    private static $services = [];

    /**
     * @param array $options
     * @param array $data
     * @param array $services
     * @return Service|SingletonService
     * @throws Exception
     */
    final public static function getInstance(array $options = [], array $data = [], array $services = [])
    {
        $serviceClass = static::class;

        if (isset(self::$services[$serviceClass])) {
            return self::$services[$serviceClass];
        }

        return self::$services[$serviceClass] = parent::getInstance($options, $data, $services);
    }

    final public function __clone()
    {
    }

    final public function __wakeup()
    {
    }
}