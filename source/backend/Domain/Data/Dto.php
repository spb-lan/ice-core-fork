<?php

namespace Ifacesoft\Ice\Core\Domain\Data;

use Ifacesoft\Ice\Core\Domain\Exception\Error;
use Ifacesoft\Ice\Core\Domain\Singleton;
use Ifacesoft\Ice\Core\Domain\Value\ArrayValue;
use Ifacesoft\Ice\Core\Domain\Value\ValueObject;

class Dto extends ArrayValue
{
    private $id = null;

    /**
     * @return null|string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|int $id
     * @return Dto
     */
    public function setId($id)
    {
        if (!($this instanceof Singleton)) {
            $this->id = $id;
        }

        return $this;
    }

    /**
     * @param array $data
     * @return Dto|ValueObject
     */
    public static function create($data = [])
    {
        if (static::class === self::class && !$data) {
            return EmptyDto::create();
        }

        return parent::create($data);
    }

//    /**
//     * @param $name
//     * @param $arguments
//     * @return mixed
//     * @throws Exception
//     */
//    final public function __call($name, $arguments)
//    {
//        return $arguments
//            ? $this->get($name, [])
//            : $this->get($name);
//    }

    /**
     * @param $paramName
     * @return mixed
     */
    final public function getRaw($paramName)
    {
        return $this->getValue()[$paramName];
    }

    /**
     * @param $paramNames
     * @param array $default
     * @return mixed
     * @throws Exception
     */
    final public function get($paramNames = [], $default = null)
    {
        $data = $this->getValue();

        if (empty($paramNames)) {
            $params = $data->getArrayCopy();

            if (is_array($params)) {
                return $params;
            }

            return empty($params) ? null : reset($params);
        }

        // не установлено дефолтное значение
        $isRequired = func_num_args() < 2;

        if ($isRequired && get_class($this) === EmptyDto::class) {
            throw Error::create('EmptyDto not can paramNames', $paramNames);
        }

        if ($default === true) {
            $default = is_array($paramNames) ? [] : null;
        }

        $isSingleValue = !is_array($paramNames) || (!$isRequired && !is_array($default));

        $isArrayReturn = $isRequired ? is_array($paramNames) : (is_array($default) || is_array($paramNames));

        $default = !is_array($default) ? [$default] : (array)$default;

        $params = [];

//        dump($paramNames);
//        dump(func_num_args());
//        dump($isRequired);
//        dump($isSingleValue);
//        dump($isArrayReturn);
//        dump($data);

        foreach ((array)$paramNames as $alias => $name) {
            if (is_int($alias)) {
                $alias = $name;
            }

            if (empty($name)) {
                throw Error::create('Dto param name is empty');
            }

            if (isset($data[$name])) {
                $params[$alias] = $data[$name];
            } else {
                $params[$alias] = $data;

                foreach (explode('/', $name) as $keyPart) {
                    if (!isset($params[$alias][$keyPart])) {
                        if ($isRequired) {
                            throw Error::create(__METHOD__, 'Required paramName ' . $name . ' (' . $keyPart . ') in ' . get_class($this) . ':' . ($this->getId() ?? 'null'), $params);
                        }

                        if ($isSingleValue) {
                            $params[$alias] = $isArrayReturn ? $default : reset($default);
                        } else {
                            $params[$alias] = $default[$alias] ?? null;
                        }

                        break;
                    }

                    $params[$alias] = $params[$alias][$keyPart];
                }
            }

            if ($isSingleValue) {
                if ($isArrayReturn) {
                    return (array)$params[$alias];
                }

                return is_array($params[$alias]) && !$isArrayReturn
                    ? (empty($params[$alias]) ? null : reset($params[$alias]))
                    : $params[$alias];
            }


        }

        return $params;
    }

    /**
     * @param string|array $paramName
     * @return Dto
     * @throws Exception
     */
    final public function getDto($paramName)
    {
        return self::create($this->get($paramName, []));
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'value' => parent::jsonSerialize()
        ];
    }

    public function serialize()
    {
        return serialize([
            'id' => $this->id,
            'value' => parent::serialize()
        ]);
    }

    public function unserialize($serialized)
    {
        $data = \unserialize($serialized);

        $this->id = $data['id'];

        parent::unserialize($data['value']);
    }
}
