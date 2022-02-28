<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core\Application;

use Exception;
use ArrayObject;
use Ifacesoft\Ice\Core\Domain\Core\Config;
use Ifacesoft\Ice\Core\Domain\Core\Module;
use Ifacesoft\Ice\Core\Domain\Core\Route;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Message\Request;
use Ifacesoft\Ice\Core\Domain\Value\ArrayValue;
use Ifacesoft\Ice\Core\Infrastructure\Core\Container;
use Ifacesoft\Ice\Core\Infrastructure\Core\Loader;
use Ifacesoft\Ice\Core\Infrastructure\Core\Service;
use Ifacesoft\Ice\Core\Infrastructure\Core\SingletonService;
use Ifacesoft\Ice\Core\Infrastructure\Repository\Configuration;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application;
use Ifacesoft\Ice\Core\Infrastructure\Transformer\Value\DefaultValue;
use Throwable;

final class Router extends SingletonService
{
    protected static function config()
    {
        return array_merge_recursive(
            [
                'services' => [
                    'loader' => [
                        'class' => Loader::class
                    ]
                ],
            ],
            parent::config()
        );
    }

    /**
     * @param Request $request
     * @param string $defaultRouteName
     * @return Route|Dto
     * @throws Throwable
     */
    public function getRoute(Request $request, $defaultRouteName = 'ifacesoft_ice-core_routeNotFound')
    {
        foreach ([$request->get('method'), Request::METHOD_ANY] as $method) {
            foreach ($this->getParam($method, [DefaultValue::class => ['value' => []]]) as $name => $route) {
                $baseMatches = [];
                preg_match_all($route['pattern'], $request->get('uri'), $baseMatches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

                if (!$baseMatches) {
                    continue;
                }

                if (count($baseMatches[0]) - 1 < count($route['params'])) {
                    $baseMatches[0][] = '';
                }

                $route['params'] = array_combine($route['params'], array_slice($baseMatches[0], 1));

                $route['name'] = $name;

                $route['request'] = $request;

                return Route::create($route);
            }
        }

        return Route::create($this->getParam(Request::METHOD_ANY . '/' . $defaultRouteName));
    }

    /**
     * @param array $routes
     * @return Dto
     * @throws Throwable
     */
    protected function createParams(array $routes)
    {
        /** @var Configuration $configuration */
        $configuration = $this->getService('configuration');

        /** @var Loader $loader */
        $loader = $this->getService('loader');

        $loader->find(
            Module::PATH_SOURCE,
            'Ifacesoft\Ice\Core\Application\Action\\',
            'php',
            ['isRequired' => false],
            static function ($found) use ($configuration, &$routes) {
                $foundRoutes = [];

                $modulePrefix = str_replace('/', '_', $found['module']->getId()) . '_';

                foreach ($configuration->getServiceClassConfig($found['class'])->get(['routes'])['routes'] as $routeName => $route) {
                    $route = ArrayValue::create($route)->receive(['route', 'params' => [], 'method' => ['ANY']]);

                    if (substr_count($route['route'], '{$') !== count($route['params'])) {
                        throw new Exception('Count of params in ' . $route['route'] . ' not equal with count of defined params ' . substr_count($route['route'], '{$') . ':' . count($route['params']));
                    }

                    $replace = [];
                    $params = [];

                    foreach ($route['params'] as $routeParamName => $routeParamOptions) {
                        if (!is_array($routeParamOptions)) {
                            $routeParamOptions = ['pattern' => $routeParamOptions];
                        }

                        $routeParamOptions = ArrayValue::create($routeParamOptions)->receive(['pattern', 'optional' => false]);

                        $params[] = $routeParamName;
                        $replace['{$' . $routeParamName . '}'] = $routeParamOptions['optional']
                            ? '(?:' . $routeParamOptions['pattern'] . ')?'
                            : $routeParamOptions['pattern'];
                    }

                    foreach ($route['method'] as $method) {
                        $routes[$method][$modulePrefix . $routeName] = [
                            'pattern' => strtr('#^' . $found['module']->get('context') . $route['route'] . '$#', $replace),
                            'params' => $params,
                            'actionClass' => $found['class']
                        ];
                    }
                }

                return null;
            }
        );

        return parent::createParams($routes);
    }
}
