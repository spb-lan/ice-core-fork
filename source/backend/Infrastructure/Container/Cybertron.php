<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Container;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Data\Entity;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonContainer;
use Ifacesoft\Ice\Core\Infrastructure\Core\Transformer;
use Throwable;

class Cybertron extends SingletonContainer
{
    /**
     * @param $transformerClass
     * @return Transformer|Service
     * @throws Throwable
     */
    public function getTransformer($transformerClass) {
        return $this->get([$transformerClass]);
    }

    /**
     * @param array $data
     * @return Entity|Dto
     */
    protected function createParams(array $data)
    {
        return Entity::create($data);
    }
}
