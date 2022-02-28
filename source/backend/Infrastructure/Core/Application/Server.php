<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core\Application;

use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Message\Response;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonService;
use Ifacesoft\Ice\Core\Infrastructure\Stream\Writer;
use Throwable;

final class Server extends SingletonService
{
    protected static function config()
    {
        return array_merge_recursive(
            [
                'params' => [
                    'response' => [

                    ]
                ],
                'services' => [
                    'responseWriter' => [
                        'class' => Writer::class,
                        'options' => [
                            'resource' => 'response/stream/resource',
                            'mode' => 'response/stream/mode'
                        ]
                    ],
                    'errorWriter' => [
                        'class' => Writer::class,
                        'options' => [
                            'resource' => Writer::STDERR,
                            'mode' => 'wb'
                        ]
                    ]
                ]
            ],
            parent::config()
        );
    }

    /**
     * @return Dto|Response
     * @throws Throwable
     */
    public function getResponse()
    {
        return $this->getParam('response');
    }

    /**
     * @throws Throwable
     */
    public function sendResponse()
    {
        fwrite(fopen(Writer::STDOUT, 'wb'), $this->getResponse()->getContent());

        return;
        $this->getService('responseWriter')->write($this->getResponse()->getContent());
    }

    public function sendError() {
        $this->getService('errorWriter')->write($this->getResponse()->getError());
    }
}
