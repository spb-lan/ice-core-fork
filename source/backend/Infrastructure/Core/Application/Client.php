<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core\Application;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Message\Request;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonService;
use Throwable;

final class Client extends SingletonService
{
    protected static function config()
    {
        return array_merge_recursive(
            [
                // todo: request require
            ],
            parent::config()
        );
    }

    /**
     * @return Dto|Request
     * @throws Throwable
     */
    public function getRequest()
    {
        return $this->getParam('request');
    }
}
