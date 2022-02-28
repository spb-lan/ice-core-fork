<?php

namespace Ifacesoft\Ice\Core\Domain\Core;

use Ifacesoft\Ice\Core\Domain\Data\Dto;

class Route extends Dto
{
    const PARAM_PATTERN_ANY = '(.*)';
    const PARAM_PATTERN_LETTERS = '(\d+)';
    const PARAM_PATTERN_DIGITS = '(\d+)';
}