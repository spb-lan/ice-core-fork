<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Provider;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Data\Entity;
use Ifacesoft\Ice\Core\Infrastructure\Core\Container;
use Ifacesoft\Ice\Core\Infrastructure\Core\Data\Provider;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;

final class Registry extends Provider
{
    /** @var Entity */
    private $data = null;

    /**
     * @param array $data
     * @return Dto
     * @throws Exception
     */
    protected function createParams(array $data)
    {
        $this->data = Entity::create();

        return parent::init();
    }

    /**
     * @param Service|string $serviceClass
     * @param string|array $paramNames
     * @param mixed $default
     * @return mixed
     * @throws Exception
     */
    public function get($serviceClass, $paramNames, $default = null)
    {
        return func_num_args() === 1
            ? $this->getServiceData($serviceClass)->get($paramNames)
            : $this->getServiceData($serviceClass)->get($paramNames, $default);
    }

    /**
     * @param $serviceClass
     * @param $params
     * @return Registry
     * @throws Exception
     */
    public function set($serviceClass, $params)
    {
        try {
            $serviceData = $this->getServiceData($serviceClass);
        } catch (Exception $e) {
            $this->data->set([$serviceClass => $serviceData = Entity::create()]);
        }

        $serviceData->set($params);

        return $this;
    }

    /**
     * @param $serviceClass
     * @return Entity
     * @throws Exception
     */
    private function getServiceData($serviceClass)
    {
        return $this->data->get($serviceClass);
    }
}
