<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

class BooleanValue extends ValueObject
{
    /**
     * @param false $value
     * @return ValueObject
     */
    public static function create($value = false)
    {
        return parent::create((bool)$value);
    }
}
