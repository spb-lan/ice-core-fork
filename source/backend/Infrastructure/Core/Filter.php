<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Data\Dto;

abstract class Filter extends Service
{
    /**
     * @param Dto $data
     * @param string $name
     * @param Config $config
     * @return mixed
     */
    abstract protected function filter(Dto $data, $name, Config $config);

    /**
     * @param array $data
     * @param $name
     * @param array $options
     * @return mixed
     */
    final public function filtrate(array $data, $name, array $options = []) {
        return $this->filter(Dto::create($data), $name, Config::create($options));
    }
}