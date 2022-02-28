<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use Ifacesoft\Ice\Core\Domain\Singleton;

final class NullValue extends ValueObject implements Singleton
{
    private static $nullValue;

    public static function create($value = null)
    {
        if (self::$nullValue) {
            return self::$nullValue;
        }

        return self::$nullValue = parent::create();
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }
}
