<?php

namespace Ifacesoft\Ice\Core\Domain\Core;

use Ifacesoft\Ice\Core\Domain\Data\Dto;

final class Log extends Dto
{
    /**
     * Save into log directory
     *
     * @return $this
     */
    public function save()
    {
        return $this;
    }

    /**
     * Output into response
     *
     * @return $this
     */
    public function show()
    {
        return $this;
    }
}
