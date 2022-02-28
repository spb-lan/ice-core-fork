<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use DateTime;
use Exception;

class StringValue extends ValueObject
{
    /**
     * @param string $value
     * @return StringValue|ValueObject
     */
    public static function create($value = '')
    {
        return parent::create((string)$value);
    }

    /**
     * @param $needles
     * @param string $type
     * @return bool
     */
    public function startsWith($needles, $type = 'or')
    {
        $string = $this->getValue();

        if (empty($string)) {
            return false;
        }

        $isStartWith = false;

        foreach ((array)$needles as $needle) {
            $isStartWith = strpos($string, $needle) === 0;

            if ($type === 'or' && $isStartWith === true) {
                return $isStartWith;
            }

            if ($type === 'and' && $isStartWith === false) {
                return $isStartWith;
            }
        }

        return $isStartWith;
    }

    /**
     * @param $needles
     * @param string $type
     * @return bool
     */
    public function endsWith($needles, $type = 'or')
    {
        $string = $this->getValue();

        $isEndWith = false;

        foreach ((array)$needles as $needle) {
            $length = strlen($needle);
            $isEndWith = $length === 0 ? true : (substr($string, -$length) === $needle);

            if ($type === 'or' && $isEndWith === true) {
                return $isEndWith;
            }

            if ($type === 'and' && $isEndWith === false) {
                return $isEndWith;
            }
        }

        return $isEndWith;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function uuid4()
    {
        $length = 16;

        $data = '';

        if (function_exists('random_bytes')) {
            $data = random_bytes($length);
        } else {
            $int = IntegerValue::create();

            for ($i = 0; $i < $length; $i++) {
                $chr = chr($int->random(0, 255));
                $data .= $chr;
            }
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @param string $delimiter
     * @param int $limit
     * @return array
     */
    public function explode($delimiter = ',', $limit = PHP_INT_MAX)
    {
        return explode($delimiter, $this->getValue(), $limit);
    }

    /**
     * @param int $returnType
     * @return StringValue|string
     */
    public function md5($returnType = self::RETURN_TYPE_NATIVE)
    {
        return $this->returnValue(md5($this->getValue()), $returnType);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getValue();
    }

    /**
     * @return string
     */
    public function getPrettyTime()
    {
        $time = $this->getValue();

        $seconds = (int)$time;

        $miliseconds = round(($time - $seconds) * 1000, 0);

        $diff = (new DateTime('@0'))->diff(new DateTime("@$seconds"));

        $date = '';

        if ($diff->format('%a')) {
            $date .= $diff->format(' %a days');
        }

        if ($diff->format('%h')) {
            $date .= $diff->format(' %h hours');
        }

        if ($diff->format('%i')) {
            $date .= $diff->format(' %i min.');
        }

        if ($diff->format('%s')) {
            $date .= $diff->format(' %s sec.');
        }

        return trim($date . ' ' . $miliseconds . ' ms');
    }

    /**
     * @param int $length
     * @param string $append
     * @param int $returnType
     * @return string|StringValue
     */
    public function truncate($length = 100, $append = '...', $returnType = self::RETURN_TYPE_NATIVE)
    {
        if (!is_numeric($length)) {
            $length = 100;
        }

        return $this->returnValue(\mb_strimwidth($this->getValue(), 0, $length, $append), $returnType);
    }

    /**
     * @param int $returnType
     * @return string
     */
    public function transliterate($returnType = self::RETURN_TYPE_NATIVE)
    {
        $string = str_replace('-', ' p r o b e l ', $this->getValue());

        $string = \transliterator_transliterate(
            "Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Lower();",
            $string
        );

        $string = str_replace(' p r o b e l ', '-', $string);

        $string = str_replace(['ʹ', 'ʺ', '+', '`'], '', preg_replace('/[\s]+/', '_', $string));

        return $this->returnValue(trim($string, '_'), $returnType);
    }

    /**
     * @param string $replacement
     * @param int $returnType
     * @return ValueObject|string
     */
    public function replaceMultipleWhitespaces($replacement = ' ', $returnType = self::RETURN_TYPE_NATIVE)
    {
        return $this->returnValue(preg_replace('/\s+/', $replacement, $this->getValue()), $returnType);
    }
}
