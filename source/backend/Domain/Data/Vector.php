<?php

namespace Ifacesoft\Ice\Core\Domain\Data;

use ArrayIterator;
use Countable;
use Exception;
use Ifacesoft\Ice\Core\Domain\Value\ValueObject;
use IteratorAggregate;
use JsonSerializable;

class Vector extends ValueObject implements JsonSerializable, IteratorAggregate, Countable
{
    /**
     * @param array $data
     * @return ValueObject|Vector
     * @throws Exception
     */
    final public static function create($data = [])
    {
        if (!$data) {
            throw new Exception('Vector is empty');
        }

        return parent::create((array)$data);
    }

    /**
     * @param $scaleMin
     * @param $scaleMax
     * @param null $valueMin
     * @param null $valueMax
     * @param int $returnType
     * @return ValueObject|Vector|mixed
     * @throws Exception
     */
    public function scale($scaleMin, $scaleMax, $valueMin = null, $valueMax = null, $returnType = self::RETURN_TYPE_NATIVE)
    {
        $vector = [];

        /** @var array $value */
        $value = $this->getValue();

        if (!isset($valueMin)) {
            $valueMin = min($value);
        }

        if (!isset($valueMax)) {
            $valueMax = max($value);
        }

        if ($valueMin !== $valueMax) {
            foreach ($value as $scalar) {
                $vector[] = ($scalar - $valueMin) * ($scaleMax - $scaleMin) / ($valueMax - $valueMin) + $scaleMin;
            }
        }

        return self::init($vector);
    }

    public function softmax()
    {
        $vector = array_map('exp', $this->getValue());
        $sum = array_sum($vector);

        foreach ($vector as $i => $value) {
            $vector[$i] = $value / $sum;
        }

        return self::init($vector);
    }

    public function dotProduct(Vector $vector)
    {
        if (count($this) !== count($vector)) {
            throw new Exception('Vector length mismatch [' . count($this) . ' != ' . count($vector) . ']');
        }

        $scalar = 0;

        foreach ($this as $i => $value) {
            $scalar += $value * $vector->get($i);
        }

        return $scalar;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->getValue());
    }

    public function count()
    {
        return count($this->getValue());
    }

    public function jsonSerialize()
    {
        return $this->getValue();
    }

    public function get($index = null)
    {
        return $this->getValue($index);
    }

    /**
     * @param $scalar
     * @return Vector|ValueObject
     * @throws Exception
     */
    public function minus($scalar)
    {
        $vector = [];

        foreach ($this as $i => $value) {
            $vector[$i] = $value - $scalar;
        }

        return self::init($vector);
    }

    /**
     * @param $scalar
     * @return Vector|ValueObject
     * @throws Exception
     */
    public function plus($scalar)
    {
        $vector = [];

        foreach ($this as $i => $value) {
            $vector[$i] = $value + $scalar;
        }

        return self::init($vector);
    }

    public function avg()
    {
        $values = $this->getValue();

        return array_sum($values) / count($values);
    }
}