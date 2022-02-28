<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Container;

use Ifacesoft\Ice\Core\Domain\Core\Route;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonContainer;
use Ifacesoft\Ice\Core\Presentation\Renderer\Json;
use Throwable;

final class Renderer extends SingletonContainer
{
    /**
     * @param Route $route
     * @return Json
     * @throws Throwable
     */
    public function getRenderer(Route $route) {
        return Json::getInstance();
    }
}
