<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use Exception;
use RuntimeException;

class IntegerValue extends ValueObject
{
    const DIGITS = '0123456789';

    /**
     * @param int $value
     * @return IntegerValue|ValueObject
     */
    public static function create($value = 0)
    {
        return parent::create((int)$value);
    }

    /**
     * @param int $min
     * @param int $max
     * @param string $validDigits
     * @return int
     * @throws Exception
     */
    public function random($min = null, $max = null, $validDigits = self::DIGITS)
    {
        $validDigits = (string)$validDigits;

        if (!$validDigits || !ctype_digit($validDigits)) {
            throw new RuntimeException('Valid digits is invalid');
        }

        if ($min === null) {
            $min = 0;
        }

        if ($max === null) {
            $max = PHP_INT_MAX;
        }

        $number = '';

        foreach (array_map('intval', str_split($this->rand($min, $max))) as $digit) {
            $digit = (string)$digit;

            $number .= strpos($validDigits, $digit) === false
                ? $validDigits[$this->rand(0, strlen($validDigits) - 1)]
                : $digit;
        }

        return (int)$number;
    }

    /**
     * @param $min
     * @param $max
     * @param $defaultDigit
     * @return int
     * @throws Exception
     */
    private function rand($min, $max)
    {
        return str_pad(
            function_exists('random_int') ? random_int($min, $max) : mt_rand($min, $max),
            strlen($max),
            0
        );
    }
}