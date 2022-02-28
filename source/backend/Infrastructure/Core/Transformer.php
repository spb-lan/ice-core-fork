<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Value\ValueObject;

abstract class Transformer extends Service
{
    /**
     * @param Dto $data
     * @param string $name
     * @param Config $config
     * @return ValueObject
     */
    abstract protected function transform(Dto $data, $name, Config $config);

    /**
     * @param array $data
     * @param string $name
     * @param array $options
     * @return ValueObject
     */
    final public function transformate(array $data, $name, array $options = []) {
        return $this->transform(Dto::create($data), $name, Config::create($options));
    }
}