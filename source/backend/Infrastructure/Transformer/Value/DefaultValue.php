<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Transformer\Value;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Infrastructure\Core\Transformer;

class DefaultValue extends Transformer
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function transform(Dto $data, $name, Config $config)
    {
        return $data->get($name, $config->getRaw('value'));
    }
}