<?php

namespace Ifacesoft\Ice\Core\Domain\Message;

use Ifacesoft\Ice\Core\Domain\Core\Message;

abstract class Response extends Message
{
    final public function getContent()
    {
        return $this->get('content');
    }

    final public function getError()
    {
        return $this->get('error');
    }
}