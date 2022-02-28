<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

class JsonString extends StringValue
{
    public function decode($assoc = false, $depth = 512, $options = 0)
    {
        return json_decode($this->getValue(), $assoc, $depth, $options);
    }
}
