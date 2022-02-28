<?php

namespace Ifacesoft\Ice\Core\Domain\Data;

use Exception;

class Entity extends Dto
{
    /**
     * @param array $params
     * @param array $options
     * @return $this
     * @throws Exception
     */
    final public function set(array $params, array $options = [])
    {
        // todo:: check на immutable
        foreach ($params as $alias => $value) {
            if ($options) {
                foreach (['callbacks', 'filters'] as $option) {
                    if (empty($options[$option])) {
                        continue;
                    }

                    switch ($option) {
                        case 'callbacks':
                            foreach ((array) $options[$option] as $callback) {
                                if (!is_callable($callback)) {
                                    throw new Exception('Not callable callback');
                                }

                                list($alias, $value) = $callback($alias, $value);
                            }
                            break;
                        default:
                    }
                }
            }

            $param = $this->getValue();

            foreach (explode('/', $alias) as $name) {
                $param[$name] = null;

                $param = &$param[$name];
            }

            $param = $value;
        }

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    final public function delete(array $params)
    {
        return $this;
    }
}