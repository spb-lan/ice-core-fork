<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Renderer;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Infrastructure\Core\Renderer;

class Json extends Renderer
{
    public function render(Dto $date) {
        return $date->json();
    }
}