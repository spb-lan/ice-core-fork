<?php

namespace Ifacesoft\Ice\Core\Domain\Data;

use Ifacesoft\Ice\Core\Domain\Singleton;

final class EmptyDto extends Dto implements Singleton
{
    private static $emptyDto;

    public static function create($data = [])
    {
        if (self::$emptyDto) {
            return self::$emptyDto;
        }

        return self::$emptyDto = parent::create();
    }

    public function __clone()
    {
    }

    public function __wakeup()
    {
    }
}
