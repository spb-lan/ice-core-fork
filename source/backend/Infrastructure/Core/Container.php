<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\EmptyConfig;
use Ifacesoft\Ice\Core\Infrastructure\Repository\Configuration;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Throwable;

class Container extends Service implements ContainerInterface
{
    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string|array $id Identifier of the entry to look for.
     *
     * @return Service
     *
     * @throws Throwable
     */
    final public function get($id)
    {
        if (!$id) {
            throw new RuntimeException('Service id is empty in ' . get_class($this));
        }

        if (!is_array($id)) {
            try {
                /** @var Service $service */
                $service = $this->getParam($id);

                return $service;
            } catch (Exception $e) {
                throw new RuntimeException(get_class($this) . ': Custom service id \'' . $id . '\' not found', 0, $e);
            }
        }

        /**
         * @var Service|string $serviceClass
         * @var array $options
         * @var array $data
         * @var array $services
         */
        list($serviceClass, $options, $data, $services) = array_pad($id, 4, []);

        if ($serviceClass === self::class) {
            return $this->create($serviceClass, EmptyConfig::create(), $data, EmptyContainer::getInstance());
        }

        /** @var Configuration $configuration */
        $configuration = Configuration::getInstance();

        $config = $configuration->getServiceClassConfig($serviceClass, $options);

        $isContainer = in_array(self::class, class_parents(get_class($this)), true);

        $di = $configuration->getDi($config, $services);

        try {
            /** @var Service $service */
            $service = $this->getParam($serviceClass::serviceId($serviceClass::generateId($config, $data, $di)), []); // todo: научиться в опциях Dto::get указывать возможность бросать исключения (ам нужно бросать что-то типа Сервис не найден и именно его отлавливать)

            return $service;
        } catch (Exception $e) {
            if ($isContainer) {
                $service = $this->create($serviceClass, $config, $data, $di);

                if (!in_array(get_class($service), [self::class, EmptyContainer::class], true)) {
                    $this->add([$service]);
                }

                return $service;
            }

            throw $e;
        }
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     *
     * @throws Throwable
     * @deprecated Use Container::get(id)
     */
    final public function has($id)
    {
        return (bool)$this->get($id);
    }

    /**
     * @param array $services
     *
     * @return Container
     *
     * @throws Throwable
     */
    final public function add(array $services)
    {
        $this->getParam()->set(
            $services,
            [
                'callbacks' => static function ($alias, $service) {
                    /** @var Service $service */
                    return [$service->getServiceId(), $service];
                }
            ]
        );

        return $this;
    }

    /**
     * @param array $services
     *
     * @return Container
     *
     * @throws Throwable
     */
    final public function remove(array $services)
    {
        $this->getParam()->delete($services);

        return $this;
    }
}
