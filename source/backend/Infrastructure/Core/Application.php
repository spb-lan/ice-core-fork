<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Dotenv\Dotenv;
use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Environment;
use Ifacesoft\Ice\Core\Domain\Core\Module;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Exception\Error;
use Ifacesoft\Ice\Core\Domain\Message\Request;
use Ifacesoft\Ice\Core\Domain\Message\Response;
use Ifacesoft\Ice\Core\Domain\Value\ArrayValue;
use Ifacesoft\Ice\Core\Domain\Value\ClassObject;
use Ifacesoft\Ice\Core\Domain\Value\TimeValue;
use Ifacesoft\Ice\Core\Domain\Value\ValueObject;
use Ifacesoft\Ice\Core\Infrastructure\Container\Controller;
use Ifacesoft\Ice\Core\Infrastructure\Container\Renderer;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Client;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Router;
use Ifacesoft\Ice\Core\Infrastructure\Core\Application\Server;
use ReflectionException;
use ReflectionFunction;
use RuntimeException;
use Throwable;
use function Webmozart\Assert\Tests\StaticAnalysis\subclassOf;

final class Application extends SingletonService
{
    const ENV_CONFIG_FILE = '.env.ice';
    const APP_CONFIG_FILE = '.ice.php';
    const VENDOR_DIR = 'vendor/';

    /**
     * @param Throwable $e
     * @param int $level
     * @param string $type
     * @return string
     */
    public static function debugExceptionString(Throwable $e, $level = 0, $type = 'file')
    {
        // [11:54:45 1607504085.8945] - host:  | uri:  | referer:

        $new = " \n"; // space needed!!!
        $tab = "\t";

        $black = '';
        $yellow = '';
        $red = '';
        $blue = '';
        $cyan = '';
        $grey = '';
        $reset = '';


        if ($type === 'cli') {
            $black = "\033[40m";
            $yellow = "\033[1;33m";
            $red = "\033[1;31m";
            $blue = "\033[1;34m";
            $cyan = "\033[1;36m";
            $grey = "\033[0;37m";
            $reset = "\033[0m";
        }

        if ($type === 'html') {
            $new .= '<br\>';
            $tab .= str_repeat('&nbsp;', 4);
        }

        $message = str_replace('.php on line ', '.php:', $e->getMessage()) . ' ';

        $string = $level
            ? ''
            : '[' . \date('H:i:s') . ' ' . microtime(true) . ']' . $new;

        $offset = str_repeat($tab, $level);

        $string .= $offset . $yellow . $black . ' ' . get_class($e) . ': ' . $red . $message . $reset . $new;

        $string .= $offset . $cyan . $black . ' ' . $e->getFile() . ':' . $e->getLine() . ' ' . $reset . $new;

        if (($e instanceof Error || in_array('Ice\Core\Exception', class_parents($e))) && $e->getContext()) {
            try {
                $string .= $new . $blue . $black . ValueObject::create($e->getContext())->varExport() . $reset . $new;
            } catch (Exception $e) {
                $string .= $new . $blue . $black . ValueObject::create($e->getContext())->printR() . $reset . $new;
            } catch (Throwable $e) {
                $string .= $new . $blue . $black . ValueObject::create($e->getContext())->printR() . $reset . $new;
            }
        }

        $stackTrace = $new . $e->getTraceAsString();
        $stackTrace = str_replace('#', $offset . '#', $stackTrace);
        $stackTrace = preg_replace('/\((\d)/', ':$1', $stackTrace);
        $stackTrace = preg_replace('/(\d)\): /', '$1' . $new . $tab . $offset, $stackTrace);
        $stackTrace = preg_replace('/#(.*)\n/', $cyan . $black . '#' . '$1' . $reset . $new, $stackTrace);
        $stackTrace = preg_replace('/' . $tab . '(.*)\n/', $grey . $black . $tab . '$1' . $reset . $new, $stackTrace);

        $string .= $stackTrace . $new . $new;

        if ($type === 'html') {
//                   $moduleDir = $this->get('dir');
            $moduleDir = '/var/www/html/';
            // todo: Строчки - файлы проекта помечать жирным

            $string .= '<pre>' . str_replace($moduleDir, './', $stackTrace) . '</pre>';
        }

        if ($e = $e->getPrevious()) {
            $string .= self::debugExceptionString($e, $level + 1);
        }

        return $string;
    }

    /**
     * @param $class
     * @return Module
     * @throws Throwable
     */
    public function getClassModule($class)
    {
        foreach ($this->getModule()->getModules() as $module) {
            if (ClassObject::create($class)->startsWith($module->get('namespace'))) {
                return $module;
            }
        }

        throw new \RuntimeException('Module for class ' . $class . ' not found');
    }

    /**
     * @return Module|Dto
     * @throws Throwable
     */
    public function getModule()
    {
        return $this->getParam('module');
    }

    /**
     * @return Environment|Dto
     * @throws Throwable
     */
    public function getEnvironment()
    {
        return $this->getParam('environment');
    }

    /**
     * @param Request|string $requestClass
     * @param Response|string $responseClass
     * @throws Throwable
     */
    public function handle($requestClass, $responseClass)
    {
        Loader::getInstance();

        $startTime = $this->getParam('startTime');

        fwrite(STDOUT, "\033[0;36m" . 'Start' . TimeValue::create($startTime)->format() . ']' . "\033[0m" . "\n\n");

        $bootstrapingProfileTime = $this->profileTime('bootstraping', $this->getParam('startTime'), $this->getParam('profiling'));

        /** @var Client $client */
        $client = Client::getInstance([], ['request' => $requestClass::create([])]);

        /** @var Router $router */
        $router = Router::getInstance();

        /** @var Controller $controller */
        $controller = Controller::getInstance();

        /** @var Renderer $renderer */
        $renderer = Renderer::getInstance();

        $route = $router->getRoute($client->getRequest());

        $routingProfileTime = $this->profileTime('routing', $bootstrapingProfileTime, $this->getParam('profiling'));

        $response = $responseClass::create([
            'content' => $renderer->getRenderer($route)->render($controller->getAction($route)->call())
        ]);


        $runningProfileTime = $this->profileTime('running', $routingProfileTime, $this->getParam('profiling'));

        /** @var Server $server */
        $server = Server::getInstance([], ['response' => $response]);

        $server->sendResponse();

        $this->profileTime("\n" . 'outputting', $runningProfileTime, $this->getParam('profiling'));


        fwrite(STDOUT, "\033[0;36m" . "\n" . 'Total: ' . TimeValue::create(microtime(true) - $startTime)->getPrettyTime() . "\033[0m" . "\n");
    }

    private function profileTime($tag, $startTime, $isProfiling)
    {
        $finishTime = microtime(true);

        if ($isProfiling) {
            fwrite(STDOUT, "\033[0;36m" . $tag . ': ' . TimeValue::create($finishTime - $startTime)->getPrettyTime() . "\033[0m" . "\n");
        }

        return $finishTime;
    }

    /**
     * @param array $data
     * @return Dto
     * @throws Throwable
     */
    protected function createParams(array $data)
    {
        $autoloadPath = self::VENDOR_DIR . 'autoload.php';

        $length = strlen($autoloadPath);

        $appPath = null;

        foreach (get_included_files() as $file) {
            if (substr($file, -$length) === $autoloadPath) {
                $appPath = strstr($file, $autoloadPath, true);
                break;
            }
        }

        if (is_file($appPath . self::ENV_CONFIG_FILE)) {
            Dotenv::createImmutable($appPath, self::ENV_CONFIG_FILE)->load();
        }

        $data['module'] = $this->createModule($appPath);

        $data['environment'] = $this->createEnvironment(null, $data['module']);

        $this->setLocale($data['environment']);
        $this->setTimezone($data['environment']);
        $this->setPhpSettings($data['environment']);

        $data['environment']->reset(['parent']);

        return parent::createParams($data);
    }

    /**
     * @param $appPath
     * @param null $path
     * @param array $overwriteData
     * @return Module
     * @throws Exception
     */
    private function createModule($appPath, $path = null, array $overwriteData = [])
    {
        $moduleDir = $appPath . ($path ? self::VENDOR_DIR . $path . '/' : '');

        $appConfigData = $this->getAppConfigData($moduleDir, $overwriteData);

        $appConfigData['dir'] = $moduleDir;

        $modules = [];

        foreach ($appConfigData['modules'] as $modulePath => $moduleData) {
            $modules[$modulePath] = isset($modules[$modulePath])
                ? $this->createModule($appPath, $modulePath, $moduleData)->merge($modules[$modulePath]->get())
                : $this->createModule($appPath, $modulePath, $moduleData);

            $appConfigData['environments'] = array_merge_recursive(
                $appConfigData['environments'],
                $modules[$modulePath]->get('environments', [])
            );

            $depModules = $modules[$modulePath]->get('modules', []);

            $intersectModules = array_intersect_key($depModules, $modules);

            /**
             * @var string $depModulePath
             * @var Module $depModule
             */
            foreach ($depModules as $depModulePath => $depModule) {
                if (isset($intersectModules[$depModulePath])) {
                    $depModule->merge($modules[$depModulePath]->get());

                    unset($modules[$depModulePath]);
                }

                $appConfigData['environments'] = array_merge_recursive(
                    $appConfigData['environments'],
                    $depModule->get('environments', [])
                );

                $modules[$depModulePath] = $depModule;
            }

            $modules[$modulePath]->reset(['modules', 'environments']);
        }

        $appConfigData['modules'] = $modules;

        $module = Module::create($appConfigData);

        return $module->setId($module->get('vendor') . '/' . $module->get('name'));
    }

    /**
     * @param string $moduleDir
     * @param array $overwriteData
     * @return array
     */
    private function getAppConfigData($moduleDir, array $overwriteData)
    {
        $appConfigFilePath = $moduleDir . self::APP_CONFIG_FILE;

        if (!file_exists($appConfigFilePath)) {
            $composerData = json_decode(file_get_contents($moduleDir . 'composer.json'), true);

            $name = explode('/', $composerData['name']);

            $phpArrayString = ArrayValue::create([
                'vendor' => $name[0],
                'name' => $name[1],
                'namespace' => 'My\Project\\',
                'alias' => 'Proj',
                'description' => 'My module',
                'url' => 'https://',
                'type' => 'module',
                'context' => '/' . mb_strtolower(str_replace('-', '/', $name[1])),
                'pathes' => [
                    'config' => 'config/',
                    'source' => 'source/backend/',
                    'resource' => 'source/resource/',
                ],
                'environments' => [
                    'prod' => [
                        'pattern' => '/^' . $name[1] . '\.prod\.local$/',
                    ],
                    'test' => [
                        'pattern' => '/^' . $name[1] . '\.test\.local$/',
                    ],
                    'dev' => [
                        'pattern' => '/^' . $name[1] . '\.dev\.local$/',
                    ],
                ],
                'modules' => [
                    'ifacesoft/ice-core' => [],
                ]
            ])->toPhpArrayString();

            file_put_contents($appConfigFilePath, $phpArrayString);
        }

        return array_merge_recursive($overwriteData, require $appConfigFilePath);
    }

    /**
     * @param null $name
     * @param Module|null $module
     * @return Dto|Environment
     * @throws Throwable
     */
    private function createEnvironment($name = null, Module $module = null)
    {
        if (!$module) {
            $module = $this->getParam('module');
        }

        /** @var Environment $environment */
        $environment = null;

        $environments = $module->get(['environments'])['environments'];

        $serverHost = gethostname();

        if (!$name) {
            foreach ($environments as $environmentName => $environmentData) {
                $environmentData['hostname'] = $serverHost;

                $environment = Environment::create($environmentData)->setId($environmentName);

                $pattern = $environment->get('pattern');

                $matches = [];

                preg_match($pattern, $serverHost, $matches);

                if (!empty($matches)) {
                    break;
                }
            }

            if (!$environment) {
                throw new RuntimeException('Environment for host ' . $serverHost . ' not found', 0, null);
            }
        } else {
            $environments[$name]['hostname'] = $serverHost;

            $environment = Environment::create($environments[$name])->setId($name);
        }

        while ($parent = $environment->get('parent', null)) {
            $environment
                ->reset(['parent'])
                ->merge($environments[$parent]);
        }

        return $environment;
    }

    /**
     * @param Environment $environment
     * @throws Exception
     */
    private function setLocale(Environment $environment)
    {
        $locales = [
            'LC_CTYPE' => LC_CTYPE,
            'LC_COLLATE' => LC_COLLATE,
            'LC_TIME' => LC_TIME,
            'LC_NUMERIC' => LC_NUMERIC,
            'LC_MONETARY' => LC_MONETARY,
            'LC_MESSAGES' => LC_MESSAGES,
            'LC_ALL' => LC_ALL
        ];

        if ($locale = $environment->get('php/functions/setlocale', [])) {
            if (is_string($locale[0]) && is_numeric($locale[0])) {
                $locale[0] = $locales[$locale[0]];
            }

            $this->callFunctionPhp('setlocale', $locale);
        } else {
            $categories = [];

            exec('locale', $categories);

            foreach ($categories as $locale) {
                $locale = explode('=', $locale);

                if (!in_array($locale[0], array_keys($locales))) {
                    continue;
                }

                $locale[0] = $locales[$locale[0]];

                if (!isset($locale[1])) {
                    $locale[1] = '';
                } else {
                    $locale[1] = \trim($locale[1], '"');
                }

                if ($locale[1]) {
                    $this->callFunctionPhp('setlocale', $locale);
                }
            }
        }

        \setlocale(LC_NUMERIC, 'C');

        $environment->merge(['locale' => \setlocale(LC_ALL, 0)]);
    }

    /**
     * @param $function
     * @param $args
     * @throws ReflectionException
     */
    private function callFunctionPhp($function, $args)
    {
        if ($args === null || $args === []) {
            return;
        }

        $function = $this->getReflectionFunctionPhp($function);

        $function->invokeArgs(\array_slice((array)$args, 0, \count($function->getParameters())));
    }

    /**
     * @param $function
     * @return ReflectionFunction
     * @throws ReflectionException
     */
    private function getReflectionFunctionPhp($function)
    {
        if (\strpos($function, '\\') !== false || !\function_exists('\\' . $function)) {
            throw Exception::create(__CLASS__, ['Function {$0} is not php function', $function]);
        }

        return new ReflectionFunction('\\' . $function);
    }

    /**
     * @param Environment $environment
     * @throws Exception
     */
    private function setTimezone(Environment $environment)
    {
        $timezone = $environment->get('php/functions/date_default_timezone_set', null);

        if (!$timezone) {
            $timezone = 'UTC'; // \date_default_timezone_get(); @todo Вернуть обратно

            if (\is_link('/etc/localtime')) {
                // Mac OS X (and older Linuxes)
                // /etc/localtime is a symlink to the
                // timezone in /usr/share/zoneinfo.
                $filename = \readlink('/etc/localtime');
                $pos = \strpos($filename, '/usr/share/zoneinfo/');
                if ($pos !== false) {
                    $timezone = \substr($filename, $pos + 20);
                }
            } elseif (\is_file('/etc/timezone')) {
                // Ubuntu / Debian.
                $data = \file_get_contents('/etc/timezone');
                if ($data) {
                    $timezone = $data;
                }
            } elseif (\is_file('/etc/sysconfig/clock')) {
                // RHEL / CentOS
                $data = \parse_ini_file('/etc/sysconfig/clock');
                if (!empty($data['ZONE'])) {
                    $timezone = $data['ZONE'];
                }
            }
        }

        \date_default_timezone_set($timezone);

        $environment->merge(['timezone' => \date_default_timezone_get()]);
    }

    /**
     * @param Environment $environment
     * @throws Exception
     */
    private function setPhpSettings(Environment $environment)
    {
        foreach ($environment->get('php/functions', []) as $function => $param_arr) {
            $this->callFunctionPhp($function, $param_arr);
        }

        foreach ($environment->get('php/ini_set', []) as $varname => $newvar) {
            $this->iniSetPhp($varname, $newvar);
        }
    }

//    private function terminate(Throwable $e)
//    {
//        die($this->debugString(debug_backtrace()));
//    }
//
//    private function debugString(array $backtrace)
//    {
//        $string = '';
//
////        $moduleDir = $this->getModule()->get('dir');
//
//        foreach (debug_backtrace() as $stack) {
//            foreach ($stack['args'] as $args) {
//                if (is_array($args)) {
//                    if ($stack['function'] !== __FUNCTION__) {
//                        $string .= $this->debugString($args);
//                    }
//
//                    continue;
//                }
//
//                if ($args instanceof Throwable) {
//                    $string .= $this->debugExceptionString($args);
//                }
//            }
//        }
//
//        return $string;
//    }

    /**
     * @param $varname
     * @param $newvalue
     */
    private function iniSetPhp($varname, $newvalue)
    {
        ini_set($varname, \is_array($newvalue) ? \reset($newvalue) : $newvalue);
    }
}
