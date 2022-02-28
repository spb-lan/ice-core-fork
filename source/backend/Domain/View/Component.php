<?php

namespace Ifacesoft\Ice\Core\Domain\View;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Infrastructure\Core\Renderer;

abstract class Component extends Dto
{
    final function render(Renderer $renderer)
    {
        return $renderer->render($this);
    }
}