<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Transformer\Value;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Value\ArrayValue;
use Ifacesoft\Ice\Core\Infrastructure\Core\Transformer;

class ArrayCallback extends Transformer
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function transform(Dto $data, $name, Config $config)
    {
        $method = $config->get('method');

        if ($method === 'trim') {
            dump($data); die();
        }

        return ArrayValue::create($data->get([$name]))->$method();
    }
}