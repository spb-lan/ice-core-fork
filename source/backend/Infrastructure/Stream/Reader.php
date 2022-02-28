<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Stream;

use Ifacesoft\Ice\Core\Infrastructure\Core\Stream;

class Reader extends Stream
{
    const STDIN = 'php://stdin';
    const INPUT = 'php://input';

    public function read()
    {

    }
}