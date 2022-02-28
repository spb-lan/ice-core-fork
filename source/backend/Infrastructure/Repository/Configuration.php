<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Repository;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Core\EmptyConfig;
use Ifacesoft\Ice\Core\Infrastructure\Core\Container;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonRepository;
use Throwable;

final class Configuration extends SingletonRepository
{
    /**
     * @param Config $config
     * @param Service[] $services
     * @return Container|Service
     * @throws Throwable
     */
    public function getDi(Config $config, array $services)
    {
        foreach ($config->get('services', []) as $serviceAlias => $serviceOptions) {
            if (array_key_exists($serviceAlias, $services)) {
                continue;
            }

            $services[$serviceAlias] = $this->service(Config::create($serviceOptions));
        }

        return Container::getInstance([], $services);
    }

    /**
     * @param Config $serviceConfig
     * @return Service
     * @throws Throwable
     */
    private function service(Config $serviceConfig)
    {
        /** @var Service|string $serviceClass */
        $serviceClass = $serviceConfig->get('class');

        $serviceOptions = $serviceConfig->get('options', []);

        $serviceData = $serviceConfig->get('data', []);

        $serviceServices = $serviceConfig->get('services', []);

        return $serviceClass::getInstance($serviceOptions, $serviceData, $serviceServices);
    }

    /**
     * @param Service|string $serviceClass
     * @param array $options
     * @return Config
     * @throws Throwable
     */
    public function getServiceClassConfig($serviceClass, array $options = [])
    {
        $options = array_merge_recursive(
            $options,
            $serviceClass::config()
        );

        if (!$options) {
            return EmptyConfig::create();
        }

        $configId = $serviceClass . '/' . md5(json_encode($options));

        /** @var Config $config */
        try {
            $config = $this->get($configId);
        } catch (Exception $e) {
            $config = Config::create($options);

            $this->add([$config]);
        }

        return $config;
    }
}
