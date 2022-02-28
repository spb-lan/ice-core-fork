<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Stream;

use Ifacesoft\Ice\Core\Infrastructure\Core\Stream;

class Writer extends Stream
{
    const STDOUT = 'php://stdout';
    const STDERR = 'php://stderr';
    const OUTPUT = 'php://output';

    public function write($string)
    {

    }
}