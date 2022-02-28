<?php

namespace Ifacesoft\Ice\Core\Domain\Message;

use ArrayObject;
use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Message;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Value\ArrayValue;

abstract class Request extends Message
{
    const METHOD_ANY = 'ANY';
    const METHOD_UNKNOWN = 'UNKNOWN';

    const PARAMS_ALL = 'all';
    const PARAMS_GET = 'get';

    /**
     * @return string
     * @throws Exception
     */
    abstract public function getMethod();

    /**
     * @return string
     * @throws Exception
     */
    abstract public function getUri();

    /**
     * @return string
     * @throws Exception
     */
    abstract public function getProtocol();

    /**
     * @return string
     * @throws Exception
     */
    abstract public function getTime();

    /**
     * @return string
     * @throws Exception
     */
    abstract public function getAgent();

    /**
     * @param null $paramsNames
     * @param string $type
     * @return array|Dto
     * @throws Exception
     */
    abstract public function getParams($paramsNames = null, $type = 'all');

    /**
     * @param ArrayObject $value
     * @return ArrayValue|void
     * @throws Exception
     */
    protected function init($value)
    {
        return parent::init(
            ArrayValue::create($value)
                ->receive([
                    'method' => $this->getMethod(),
                    'uri' => $this->getUri(),
                    'protocol' => $this->getProtocol(),
                    'time' => $this->getTime(),
                    'agent' => $this->getAgent(),
                    'params' => $this->getParams(null, null)
                ])
        );
    }
}
