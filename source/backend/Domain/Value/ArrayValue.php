<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use ArrayAccess;
use ArrayObject;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class ArrayValue extends ValueObject implements JsonSerializable, IteratorAggregate, ArrayAccess, Countable
{
    const TRIM_SIDE_BOTH = 1;
    const TRIM_SIDE_LEFT = 2;
    const TRIM_SIDE_RIGHT = 3;

    /**
     * @param array $data
     * @return ArrayValue|ValueObject
     */
    public static function create($data = [])
    {
        return parent::create((array)$data);
    }

    protected function init($value)
    {
        return parent::init(new ArrayObject((array)$value));
    }

    /**
     * @return ArrayObject
     */
    final protected function getArrayObject()
    {
        return $this->getValue();
    }

    /**
     * @inheritDoc
     */
    final public function getIterator()
    {
        return $this->getArrayObject()->getIterator();
    }

    /**
     * @inheritDoc
     */
    final public function offsetExists($offset)
    {
        return $this->getArrayObject()->offsetExists($offset);
    }

    /**
     * @inheritDoc
     */
    final public function offsetGet($offset)
    {
        return $this->getArrayObject()->offsetGet($offset);
    }

    /**
     * @inheritDoc
     */
    final public function offsetSet($offset, $value)
    {
        $this->getArrayObject()->offsetSet($offset, $value);
    }

    /**
     * @inheritDoc
     */
    final public function offsetUnset($offset)
    {
        $this->getArrayObject()->offsetUnset($offset);
    }

    /**
     * @inheritDoc
     */
    final public function count()
    {
        return $this->getArrayObject()->count();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->getValue();
    }

    /**
     * @param array $vars
     * @param int $returnType
     * @return array
     * @todo: что-то похожее надо для Dto
     */
    public function receive(array $vars, $returnType = self::RETURN_TYPE_NATIVE)
    {
        $var = $this->getValue()->getArrayCopy();

        $data = [];

        foreach ($vars as $key => $value) {
            if (is_int($key)) {
                $key = $value;
                $value = null;
            }

            $data[$key] = $var[$key] ?? $value;

            if (is_array($value)) {
                $data[$key] = (array)$data[$key];
            }
        }

        return $this->returnValue($data, $returnType);
    }

    /**
     * @param array $columns
     * @param array $groups
     * @param array $indexFieldNames
     * @param array $indexGroupFieldNames
     * @param array $aggregate
     * @param array $exclude
     * @return array
     */
    public function group(array $columns, array $groups = [], array $indexFieldNames = [], array $indexGroupFieldNames = [], array $aggregate = [], array $exclude = [])
    {
        foreach ($columns as $columnAlias => $columnName) {
            if (is_int($columnAlias)) {
                $columns[$columnName] = $columnName;
                unset($columns[$columnAlias]);
            }
        }

        if (!$groups) {
            $groups = ['items' => '*'];
        }

        foreach ($groups as $groupKey => &$group) {
            if (!isset($indexGroupFieldNames[$groupKey])) {
                $indexGroupFieldNames[$groupKey] = null;
            }

            if (is_array($group)) {
                foreach ($group as $fieldAlias => $fieldName) {
                    if (is_int($fieldAlias)) {
                        $group[$fieldName] = $fieldName;
                        unset($group[$fieldAlias]);
                    }
                }
            }
        }
        unset($group);

        $columnKeys = array_flip($columns);

        $indexes = [];
        $groupIndexes = [];

        $itemCount = 0;
        $groupsCount = [];

        if ($indexFieldNames === true) {
            $indexFieldNames = $columns;
        }

        $indexKeys = empty($indexFieldNames) ? $columnKeys : array_flip((array)$indexFieldNames);

        $excludeKeys = array_flip($exclude);

        $items = [];

        foreach ($this as $key => $item) {
            $item = array_diff_key($item, $excludeKeys);

            $column = [];

            foreach ($columns as $columnAlias => $columnName) {
                if (!array_key_exists($columnName, $item)) {
                    throw new \Error('Column ' . $columnName . ' not found in item');
                }

                $column[$columnAlias] = $item[$columnName];
            }

            $index = implode('__', array_filter($column));

            if (!isset($indexes[$index])) {
                $indexes[$index] = empty($indexFieldNames)
                    ? $itemCount++
                    : implode('__', array_intersect_key($item, $indexKeys));

                foreach (array_keys($groups) as $groupKey) {
                    $groupIndexes[$groupKey] = [];
                    $column[$groupKey] = [];
                }

                foreach ($aggregate as $aggregateColumn => $aggregateFunc) {
                    $aggregateColumnName = $aggregateColumn . '__' . strtolower($aggregateFunc);

                    switch ($aggregateFunc) {
                        case 'MAX':
                            $column[$aggregateColumnName] = null;
                            break;
                        case 'SUM':
                        case 'COUNT':
                            $column[$aggregateColumnName] = 0;
                            break;
                        default:
                    }
                }

                $items[$indexes[$index]] = $column;
            }

            foreach ($groups as $groupKey => $group) {
                if (!isset($groupsCount[$indexes[$index] . '__' . $groupKey])) {
                    $groupsCount[$indexes[$index] . '__' . $groupKey] = 0;
                }

                $groupColumn = [];

                if (is_array($group)) {
                    foreach ($group as $groupAlias => $groupName) {
                        if (!array_key_exists($groupName, $item)) {
                            throw new \Error('Group column ' . $groupName . ' not found in item');
                        }

                        $groupColumn[$groupAlias] = $item[$groupName];
                    }
                } else if ($group === '*') {
                    $groupColumn = array_diff_key($item, $columnKeys);
                } else {
                    $groupColumn = array_intersect_key($item, array_flip((array)$group));
                }

                if (!array_filter($groupColumn)) {
                    continue;
                }

                $indexGroupKeys = empty($indexGroupFieldNames[$groupKey])
                    ? $groupColumn
                    : array_flip((array)$indexGroupFieldNames[$groupKey]);

                $groupValue = $group !== '*' && !is_array($group)
                    ? reset($groupColumn)
                    : $groupColumn;

                if ($groupValue !== null && !in_array($groupValue, $items[$indexes[$index]][$groupKey])) {
                    $groupIndexes[$groupKey] = empty($indexGroupFieldNames[$groupKey])
                        ? $groupsCount[$indexes[$index] . '__' . $groupKey]++
                        : implode('__', array_intersect_key($groupColumn, $indexGroupKeys)); // todo: порядок должен соответствовать $indexGroupKeys (знвчения должны браться из $groupColumn)

                    $items[$indexes[$index]][$groupKey][$groupIndexes[$groupKey]] = $groupValue;

                    foreach ($aggregate as $aggregateColumn => $aggregateFunc) {
                        $aggregateColumnName = $aggregateColumn . '__' . strtolower($aggregateFunc);

                        switch ($aggregateFunc) {
                            case 'MAX':
                                if ($items[$indexes[$index]][$aggregateColumnName] === null || $groupValue[$aggregateColumn] > $items[$indexes[$index]][$aggregateColumnName]) {
                                    $items[$indexes[$index]][$aggregateColumnName] = $groupValue[$aggregateColumn];
                                }
                                break;
                            case 'SUM':
                                $items[$indexes[$index]][$aggregateColumnName] += $groupValue[$aggregateColumn];
                                break;
                            case 'COUNT':
                                $items[$indexes[$index]][$aggregateColumnName]++;
                                break;
                            default:
                        }
                    }
                }
            }
        }

        return $items;
    }

    /**
     * @param string $characterMask
     * @param int $side
     * @param int $returnType
     * @return array|ArrayValue
     */
    public function trim($characterMask = " \t\n\r\0\x0B", $side = self::TRIM_SIDE_BOTH, $returnType = self::RETURN_TYPE_NATIVE)
    {
        switch ($side) {
            case self::TRIM_SIDE_BOTH:
                $value = array_map(
                    static function ($string) use ($characterMask) {
                        return trim($string, $characterMask);
                    },
                    $this->getValue()
                );
                break;
            case self::TRIM_SIDE_LEFT:
                $value = array_map(
                    static function ($string) use ($characterMask) {
                        return ltrim($string, $characterMask);
                    },
                    $this->getValue()
                );
                break;
            case self::TRIM_SIDE_RIGHT:
                $value = array_map(
                    static function ($string) use ($characterMask) {
                        return rtrim($string, $characterMask);
                    },
                    $this->getValue()
                );
                break;
            default:
                $value = $this->getValue();
        }

        return $this->returnValue($value, $returnType);
    }

    public function filter($callback = null, $returnType = self::RETURN_TYPE_NATIVE)
    {
        return $callback
            ? $this->returnValue(array_filter($this->getValue()->getArrayCopy(), $callback), $returnType)
            : $this->returnValue(array_filter($this->getValue()->getArrayCopy()), $returnType);
    }

    /**
     * @param int $options
     * @param int $depth
     * @return JsonString|StringValue
     */
    public function json($options = JSON_UNESCAPED_UNICODE, $depth = 512)
    {
        return JsonString::create(json_encode($this, $options, $depth));
    }

    /**
     * Pretty formatting php data (array)
     *
     * @param  $var
     * @param bool $withPhpTag
     * @param bool $isPretty
     * @return mixed|string
     * @author dp <denis.a.shestakov@gmail.com>
     */
    public function toPhpArrayString($withPhpTag = true, $isPretty = true)
    {
        $string = $withPhpTag
            ? '<?php' . "\n" . 'return ' . var_export($this->getValue()->getArrayCopy(), true) . ';'
            : var_export($this->getValue()->getArrayCopy(), true) . ';';

        if (!$isPretty) {
            return $string;
        }

        $string = str_replace('array (', '[', $string);
        $string = str_replace('(array(', '([', $string);
        $string = str_replace('),', '],', $string);
        $string = str_replace(')],', ']),', $string);
        $string = str_replace(');', '];', $string);
        $string = preg_replace('/=>\s+\[/', '=> [', $string);
        $string = preg_replace('/=> \[\s+\]/', '=> []', $string);
        for ($i = 10; $i >= 1; $i--) {
            $string = str_replace("\n" . str_repeat(' ', $i * 2), "\n" . str_repeat("\t", $i), $string);
        }
        $string = str_replace("\t", '    ', $string);
        $string = str_replace('NULL', 'null', $string);

        return $string;
    }

    public function avg()
    {
        return array_sum($this->getValue()->getArrayCopy()) / count($this);
    }

    public function min()
    {
        return min($this->getArray());
    }

    public function max()
    {
        return max($this->getArray());
    }

    /**
     * @bug работает только со списком (последовательный числовой индекс)
     * @return mixed
     */
    public function pop()
    {
        $lastIndex = $this->count() - 1;

        $value = $this->offsetGet($lastIndex);
        $this->offsetUnset($lastIndex);

        return $value;
    }

    /**
     * @bug работает только со списком (последовательный числовой индекс)
     */
    public function push($value)
    {
        $this->getArrayObject()->append($value);

        return $this->count() - 1;
    }

    public function shuffle()
    {
        $arrayCopy = $this->getArray();

        shuffle($arrayCopy);

        $this->getArrayObject()->exchangeArray($arrayCopy);
    }

    public function chunk($size, $returnType = self::RETURN_TYPE_NATIVE)
    {
        $chunk = [];

        foreach ($this as $value) {
            $chunk[] = $value;

            if (count($chunk) === $size) {
                yield $chunk;

                $chunk = [];
            }
        }

        if ($chunk) {
            yield $chunk;
        }
    }

    public function getArray()
    {
        return $this->getArrayObject()->getArrayCopy();
    }
}
