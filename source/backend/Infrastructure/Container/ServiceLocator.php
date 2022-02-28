<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Container;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Data\Entity;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonContainer;

final class ServiceLocator extends SingletonContainer
{
    /**
     * @param array $data
     * @return Entity|Dto
     */
    protected function createParams(array $data)
    {
        return Entity::create($data);
    }
}
