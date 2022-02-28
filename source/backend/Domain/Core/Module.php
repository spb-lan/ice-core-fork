<?php

namespace Ifacesoft\Ice\Core\Domain\Core;

use Exception;

final class Module extends Config
{
    const PATH_SOURCE = 'source';

    /**
     * @param $modulePathType
     * @param bool $isMultiPath
     * @return array|string
     * @throws Exception
     */
    public function getDir($modulePathType, $isMultiPath = false)
    {
        $moduleDir = $this->get('dir');

        if (!$isMultiPath) {
            return $moduleDir . $this->get('pathes/' . $modulePathType);
        }

        $dirs = [];

        foreach ($this->get(['pathes/' . $modulePathType]) as $path) {
            foreach ((array) $path as $p) {
                $dirs[] = $moduleDir . $p;
            }
        }

        return array_unique($dirs);
    }

    /**
     * @return Module[]
     * @throws Exception
     */
    public function getModules()
    {
        return [$this->getId() => $this] + $this->get(['modules'])['modules'];
    }
}