<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Core\EmptyConfig;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Data\EmptyDto;
use Ifacesoft\Ice\Core\Domain\Value\ArrayValue;
use Ifacesoft\Ice\Core\Domain\Value\StringValue;
use Ifacesoft\Ice\Core\Infrastructure\Container\Cybertron;
use Ifacesoft\Ice\Core\Infrastructure\Container\ServiceLocator;
use Ifacesoft\Ice\Core\Infrastructure\Repository\Configuration;
use RuntimeException;
use Throwable;

abstract class Service
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Dto
     */
    private $params;

    /**
     * @var Container
     */
    private $di = null;

    /**
     * @var Config
     * @deprecated stored in configuration
     */
    private $config = null;

    /**
     *  return array_merge_recursive(
     *      [
     *          'params' => []
     *      ],
     *      parent::config()
     *  );
     *
     * @return array
     *
     * @todo Может быть когда-нибудь сделать Config implements Builder
     */
    protected static function config()
    {
        return [
            'services' => [
                'configuration' => [
                    'class' => Configuration::class
                ],
            ],
        ];
    }

    /**
     * @return Config
     * @throws Throwable
     */
    final protected function getConfig()
    {
        /** @var Configuration $configuration */
        $configuration = $this->getService('configuration');

        return $configuration->getServiceClassConfig(get_class($this));
    }

    /**
     * Service constructor.
     * @param Config|null $config
     * @param array $data
     * @param Container|null $di
     * @throws Throwable
     */
    final private function __construct(Config $config = null, array $data = [], Container $di = null)
    {
        $this->config = $config ?: Config::create();

        if (get_class($this) === EmptyContainer::class) {
            $di = $this;
        }

        $this->di = $di ?: EmptyContainer::getInstance();

        $this->id = self::generateId($this->config, $data, $this->di);

        if (get_class($this->config) !== EmptyConfig::class) {
            $this->config->setId($this->getServiceId());
        }

        if (get_class($this->di) !== EmptyContainer::class) {
            $this->di->setId($this->getServiceId());
        }

        $this->params = $this->createParams($this->getData($data));

        if (get_class($this->params) !== EmptyDto::class) {
            $this->params->setId($this->getServiceId());
        }

        $this->init();
    }

    protected function init()
    {
    }

    /**
     * @param array $data
     * @return Dto
     */
    protected function createParams(array $data)
    {
        return Dto::create($data);
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function getData(array $data)
    {
        foreach ($this->config->get('params', []) as $paramAlias => $paramOptions) {
            if (array_key_exists($paramAlias, $data)) {
                continue;
            }

            if (!is_array($paramOptions)) {
                $data[$paramAlias] = $paramOptions;
                continue;
            }

            if (array_key_exists('value', $paramOptions)) {
                $data[$paramAlias] = $paramOptions['value'];
                continue;
            }

            if (!isset($paramOptions['name'])) {
                $paramOptions['name'] = $paramAlias;
            }

            $data[$paramAlias] = $this->getDataParam(Config::create($paramOptions));
        }

        return $data;
    }

    /**
     * @param Config $paramConfig
     * @return Dto
     * @throws Exception
     */
    private function getDataParam(Config $paramConfig)
    {
        $name = $paramConfig->get('name');

        $value = null;

        foreach ($paramConfig->get(['services']) as $serviceOptions) {
            $serviceConfig = Config::create($serviceOptions);

            try {
                if ($serviceAlias = $serviceConfig->get('alias', null)) {
                    $service = $this->getService($serviceAlias);
                } elseif ($class = $serviceConfig->get('class', null)) {
                    $service = $class === get_class($this)
                        ? $this
                        : $class::getInstance();
//            } elseif ($class = $serviceConfig->get('class', null)) { // ['class', 'data',' services']
//                    ? $this
//                    : $class::getInstance();
                } else {
                    $service = $this;
                }
            } catch (Exception $e) {
                throw new RuntimeException('Service not found in di container ' . get_class($this) . ':' . $e->getMessage(), 0, $e);
            } catch (Throwable $e) {
                throw new RuntimeException('Service not found in di container ' . get_class($this) . ':' . $e->getMessage(), 0, $e);
            }

            if ($serviceMethod = $serviceConfig->get('method', null)) {
                $value = $service->$serviceMethod();

                if ($value !== null) {
                    break;
                }
            } else {
                $value = $service->params->get($serviceConfig->get('path', $name), null);
            }
        }

        /** @var Cybertron $cybertron */
        $cybertron = Cybertron::getInstance();

        foreach ($paramConfig->get('mutators', []) as $transformerClass => $transformerOptions) {
            $value = $cybertron->getTransformer($transformerClass)->transformate([$name => $value], $name, $transformerOptions);
        }

        if ($value !== null) {
            return $value;
        }

        throw new RuntimeException('Init service param not found! ' . $paramConfig->printR());
    }

    /**
     * @return string
     */
    final public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return Service
     */
    private function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string|null $paramName
     * @param array $transformers
     * @return Dto|
     * @throws Throwable
     */
    final public function getParam($paramName = null, array $transformers = [])
    {
        if (!func_num_args()) {
            return $this->params;
        }

        $isArray = is_array($paramName); // todo: need implement

        try {
            if (func_num_args() < 2) {

//                if (is_array($paramName)) {
//                    debug_print_backtrace();
//                    throw new Exception();
//                    die();
//                }

//                print_r('!!!!!!!!!!!!!');
//                var_dump($paramName);
//                var_dump('params/' . $paramName . '/accessors');
//                print_r('!!!!!!!!!!!!!');
                $transformers = $this->config->get('params/' . $paramName . '/accessors', []);
            }

            $data = [];

            if ($transformers) {
                $data = $this->params->get();

                /** @var Cybertron $cybertron */
                $cybertron = Cybertron::getInstance();

                foreach ($transformers as $transformerClass => $transformerOptions) {
                    $data[$paramName] = $cybertron->getTransformer($transformerClass)->transformate($data, $paramName, $transformerOptions);
                }

            } else {
                $data[$paramName] = $this->params->get($paramName);
            }
        } catch (Exception $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $e;
        }

        return $data[$paramName];
    }

    /**
     * @param $id
     * @return Service
     * @throws Throwable
     */
    final public function getService($id)
    {
        try {
            $service = $this->di->get($id);
        } catch (Exception $e) {
            throw new RuntimeException(get_class($this) . ': ' . 'Service id \'' . $id . '\' not found in di container', 0, $e);
        } catch (Throwable $e) {
            throw new RuntimeException(get_class($this) . ': ' . 'Service id \'' . $id . '\' not found in di container', 0, $e);
        }

        return $service;
    }

    /**
     * @param Config $config
     * @param array $data
     * @param Container $di
     * @return string
     * @throws Exception
     * @todo частое использование при получении и созжании сервиса
     */
    protected static function generateId(Config $config, array $data, Container $di)
    {
        return StringValue::create($config->getId() . '.' . Dto::create($data)->getId() . '.' . $di->getId())->md5();
    }

    /**
     * @return string
     */
    final public function getServiceId()
    {
        return static::serviceId($this->getId());
    }

    /**
     * @param $id
     * @return string
     */
    final public static function serviceId($id)
    {
        return static::class . '/' . $id;
    }

    /**
     * @param Service|string $serviceClass
     * @param Config $config
     * @param array $data
     * @param Container $di
     * @return Service
     */
    final protected function create($serviceClass, Config $config, array $data, Container $di)
    {
        if (in_array(Container::class, class_parents(get_class($this)), true)) {
            return new $serviceClass($config, $data, $di);
        }

        throw new RuntimeException('Only ServiceLocator can create Service');
    }

    /**
     * @param array $options
     * @param array $data
     * @param Service[] $services
     * @return Service
     * @throws Throwable
     */
    public static function getInstance(array $options = [], array $data = [], array $services = [])
    {
        foreach ([ServiceLocator::class, EmptyContainer::class, Configuration::class, Application::class, Cybertron::class] as $serviceClass) {
            if (static::class === $serviceClass) {
                return new $serviceClass(null, $data);
            }
        }

        if (static::class === Container::class && empty($data)) {
            return EmptyContainer::getInstance();
        }

        return ServiceLocator::getInstance()->get([static::class, $options, $data, $services]);
    }

    /**
     * @param bool $die
     */
    final public function dump($die = false)
    {
        dump($this);

        if ($die) {
            die();
        }
    }
}