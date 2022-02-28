<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Ifacesoft\Ice\Core\Domain\Data\Dto;

abstract class Renderer extends Service
{
    abstract public function render(Dto $date);
}
