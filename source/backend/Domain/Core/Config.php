<?php

namespace Ifacesoft\Ice\Core\Domain\Core;

use ArrayObject;
use Exception;
use Ifacesoft\Ice\Core\Domain\Data\Dto;

class Config extends Dto
{
    /**
     * @param array $data
     * @return Config|Dto
     */
    public static function create($data = [])
    {
        if (static::class === self::class && !$data) {
            return EmptyConfig::create();
        }

        return parent::create($data);
    }

    /**
     * @param $paramNames
     * @return $this
     */
    public function reset($paramNames)
    {
        $value = $this->getValue();

        foreach ((array)$paramNames as $paramName) {
            unset($value[$paramName]);
        }

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function merge(array $data)
    {
        /** @var ArrayObject $value */
        $value = $this->getValue();

        $value->exchangeArray(array_merge_recursive($value->getArrayCopy(), $data));

        return $this;
    }

    /**
     * @param string $paramName
     * @param array $options
     * @return Config|Dto
     * @throws Exception
     */
    final public function getConfig($paramName, array $options = [])
    {
        return self::create($this->get($paramName, $options))->setId($this->getId() . '/' . $paramName);
    }
}