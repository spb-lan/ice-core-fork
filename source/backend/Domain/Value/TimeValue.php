<?php

namespace Ifacesoft\Ice\Core\Domain\Value;

use DateTime;
use Ice\Helper\Date;

class TimeValue extends ValueObject
{
    const ZERO = '0000-01-01 00:00:00';
    const FUTURE = '2099-12-31 00:00:00';
    const FORMAT_DEFAULT = 'Y-m-d H:i:s';
    const FORMAT_DEFAULT_DATE = 'Y-m-d';
    const FORMAT_DATE = 'd.m.y';
    const FORMAT_DATETIME = 'd.m.y H:i:s';

    /**
     * @param string $value
     * @return StringValue|ValueObject
     */
    public static function create($value = 0)
    {
        return parent::create((float)$value);
    }

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

    public function format($format = self::FORMAT_DEFAULT)
    {
        return date($format, $this->getValue());
    }
}