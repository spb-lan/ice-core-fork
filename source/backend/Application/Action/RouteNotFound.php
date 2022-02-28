<?php

namespace Ifacesoft\Ice\Core\Application\Action;

use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Action;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Router;
use Throwable;

class RouteNotFound extends Action
{
    /**
     * @return array
     */
    protected static function config()
    {
        return array_merge_recursive(
            [
                'routes' => [
                    'routeNotFound' => [
                        'method' => 'ANY',
                        'route' => '/routeNotFound',
                        'params' => []
                    ]
                ],
                'services' => [
                    'router' => [
                        'class' => Router::class
                    ]
                ]
            ],
            parent::config()
        );
    }

    /**
     * @return array
     * @throws Throwable
     */
    protected function run()
    {
        return [
            'content' => 'Route Not Found!',
            'routes' => $this->getService('router')->getParam()
        ];
    }
}
