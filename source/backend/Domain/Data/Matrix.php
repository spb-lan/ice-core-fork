<?php

namespace Ifacesoft\Ice\Core\Domain\Data;

use Countable;
use Exception;
use Ifacesoft\Ice\Core\Domain\Value\ValueObject;
use IteratorAggregate;
use JsonSerializable;

class Matrix extends ValueObject implements JsonSerializable, IteratorAggregate, Countable
{
    /**
     * @param array $data
     * @return ValueObject|Vector
     * @throws Exception
     */
    final public static function create($data = [[]])
    {
        if (!$data) {
            throw new Exception('Martix is empty');
        }

        return parent::create((array)$data);
    }

    public function dotProduct(Matrix $matrix)
    {
        if (count($this) !== count($matrix)) {
            throw new Exception('Matrix length mismatch [' . count($this) . ' != ' . count($matrix) . ']');
        }

        $dotProduct = [[]];

        foreach ($this as $i => $vector) {
            foreach ($vector as $j => $value) {
                $dotProduct[$i][$j] = 0;

                foreach ($matrix as $k => $kVector) {
                    $dotProduct[$i][$j] += $this->get($i, $k) * $matrix->get($k, $j);
                }
            }
        }

        return $dotProduct;
    }

    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }

    public function getIterator()
    {
        // TODO: Implement getIterator() method.
    }

    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    public function count()
    {
        // TODO: Implement count() method.
    }
}