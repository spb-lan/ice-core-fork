<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Transformer\Value;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Value\StringValue;
use Ifacesoft\Ice\Core\Infrastructure\Core\Transformer;

class StringCallback extends Transformer
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function transform(Dto $data, $name, Config $config)
    {
        $method = $config->get('method');

        return StringValue::create($data->get($name))->$method();
    }
}