<?php

namespace Ifacesoft\Ice\Core\Application\Action;

use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Action;

class GarbageCollector_RemoveOldFiles extends Action
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
                        'method' => 'CLI',
                        'route' => '/garbage_collector/remove_old_files',
                        'params' => []
                    ]
                ],
                'directories' => [
                    'var/cache' => 7,
                    'var/temp' => 1
                ]
            ],
            parent::config()
        );
    }

    /**
     * @return array|void
     * @throws \Throwable
     */
    protected function run()
    {
        foreach ($this->getConfig()->get(['directories']) as $path => $days) {
            dump([$path => $days]);
        }
    }
}