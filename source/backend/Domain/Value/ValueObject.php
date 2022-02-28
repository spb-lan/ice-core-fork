<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use ArrayAccess;
use Exception;
use Serializable;
use Throwable;

class ValueObject implements Serializable
{
    const RETURN_TYPE_NATIVE = 1;
    const RETURN_TYPE_OBJECT = 2;

    /**
     * @var mixed
     */
    private $value = null;

    /**
     * Value constructor.
     * @param $value
     */
    final private function __construct($value)
    {
        $this->init($value);
    }

    /**
     * @param null $field
     * @return mixed
     */
    final public function getValue($field = null)
    {
        if ($field === null) {
            return $this->value;
        }

        return is_array($this->value) || $this->value instanceof ArrayAccess
            ? $this->value[$field]
            : $this->value->$field;
    }

    /**
     * @return string
     */
    public function printR()
    {
        return str_replace('Array (', '(', preg_replace('/\s{2,}/', ' ', preg_replace('/[\x00-\x1F\x7F ]/', ' ', print_r($this->value, true))));
    }

    /**
     * @return string
     */
    public function varExport()
    {
        try {
            $value = trim(var_export((array)$this->value, true));
        } catch (Exception $e) {
            return $this->printR();
        } catch (Throwable $e) {
            return $this->printR();
        }

        $value = str_replace('array (', '[', $value);
        $value = str_replace('array(', '[', $value);
        $value = str_replace('=> ' . "\n", '=> ', $value);
        $value = preg_replace('/=> \s{2,}/', '=> ', $value);
        $value = str_replace(')),', ']),', $value);
        $value = str_replace(' ),', ' ],', $value);
        $value = preg_replace('/\(\[\n\s{2,}\]\)/', '([])', $value);

        return 'return ' . substr($value, 0, -1) . '];';
    }

    /**
     * @param mixed $value
     * @return ValueObject
     */
    public static function create($value = null)
    {
        if (static::class !== NullValue::class && $value === null) {
            return NullValue::create();
        }

        return new static($value);
    }

    /**
     * @param $value
     * @return ValueObject
     */
    protected function init($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize($this->value);
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->value = unserialize($serialized);
    }

    final public function dump($die = false)
    {
        dump($this);

        if ($die) {
            die();
        }
    }

    /**'
     * @param $callback
     * @param int $returnType
     * @return mixed
     */
    final public function callback($callback, $returnType = self::RETURN_TYPE_NATIVE)
    {
        $value = is_callable($callback)
            ? $callback($this)
            : call_user_func($callback, $this);

        return $this->returnValue($value, $returnType);
    }

    /**
     * @param $value
     * @param $returnType
     * @return mixed
     */
    final protected function returnValue($value, $returnType)
    {
        /** @var ValueObject|string $class */
        $class = get_class($this);

        switch ($returnType) {
            case self::RETURN_TYPE_OBJECT:
                return $class::create($value);
            case self::RETURN_TYPE_NATIVE:
            default:
                return $value;
        }
    }
}
