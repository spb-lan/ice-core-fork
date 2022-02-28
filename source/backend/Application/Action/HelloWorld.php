<?php

namespace Ifacesoft\Ice\Core\Application\Action;

use Ifacesoft\Ice\Core\Infrastructure\Core\Application;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Action;
use Throwable;

class HelloWorld extends Action
{
    /**
     * @return array
     */
    protected static function config()
    {
        return array_merge_recursive(
            [
                'routes' => [
                    'helloWorld' => [
                        'method' => 'ANY',
                        'route' => '/helloWorld',
                        'params' => []
                    ]
                ],
                'services' => [
                    'application' => [
                        'class' => Application::class
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
            'content' => 'Hello World!',
            'env' => $this->getService('application')->getParam('environment')
        ];
    }
}