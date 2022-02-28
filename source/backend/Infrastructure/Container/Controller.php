<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Container;

use Ifacesoft\Ice\Core\Domain\Core\Route;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Data\Entity;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Action;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonContainer;
use Throwable;

final class Controller extends SingletonContainer
{
    /**
     * @param Route $route
     * @return Action|Service
     * @throws Throwable
     */
    public function getAction(Route $route) {
        return $this->get([$route->get('actionClass')]);
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
