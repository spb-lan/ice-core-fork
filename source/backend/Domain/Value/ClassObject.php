<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Module;

class ClassObject extends StringValue
{
    /**
     * @return false|string
     */
    public function getName()
    {
        $class = trim($this->getValue(), '\\');

        if (!strpos($class, '\\')) {
            return $class;
        }

        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     * @param Module $module
     * @return false|string
     * @throws Exception
     */
    public function getClassRefNamespace(Module $module)
    {
        $class = trim($this->getValue(), '\\');
        
        return substr($class, strlen($module->get('namespace')), -strlen($this->getName()));
    }
}