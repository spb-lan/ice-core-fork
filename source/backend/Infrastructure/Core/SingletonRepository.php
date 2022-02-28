<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Exception;
use Ifacesoft\Ice\Core\Infrastructure\Core\Data\Repository;

abstract class SingletonRepository extends Repository implements Singleton
{
    /**
     * @var Repository[]
     */
    private static $repositories = [];

    /**
     * @param array $options
     * @param array $data
     * @param array $services
     * @return Repository|Service
     * @throws Exception
     */
    final public static function getInstance(array $options = [], array $data = [], array $services = [])
    {
        if (isset(self::$repositories[static::class])) {
            return self::$repositories[static::class];
        }

        return self::$repositories[static::class] = parent::getInstance($options, $data, $services);
    }

    final public function __clone()
    {
    }

    final public function __wakeup()
    {
    }
}
