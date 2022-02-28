<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core\Application;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;

abstract class Action extends Service
{
    /**
     * @return array
     */
    abstract protected function run();

    public function call() {
        return Dto::create(['result' => $this->run()]);
    }
}
