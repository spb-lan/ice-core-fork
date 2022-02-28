<?php

namespace Ifacesoft\Ice\Core\Infrastructure\Core;

use Closure;
use Composer\Autoload\ClassLoader;
use Exception;
use Ifacesoft\Ice\Core\Domain\Core\Module;
use Ifacesoft\Ice\Core\Domain\Data\Dto;
use Ifacesoft\Ice\Core\Domain\Value\ClassObject;
use Ifacesoft\Ice\Core\Domain\Value\StringValue;

final class Loader extends SingletonService
{
    private $cache = null;

    /**
     * @return array
     */
    protected static function config()
    {
        return array_merge_recursive(
            [
                'services' => [
                    'application' => [
                        'class' => Application::class
                    ],
                ]
            ],
            parent::config()
        );
    }

    /**
     * @param array $data
     * @return Dto
     * @throws Exception
     */
    protected function createParams(array $data)
    {
        ClassObject::create(get_class($this));

        $autoLoaders = array_merge($data, spl_autoload_functions());

        foreach ($autoLoaders as $autoloader) {
            spl_autoload_unregister($autoloader);
        }

        array_unshift($autoLoaders, [$this, 'autoload']);

        spl_autoload_register([$this, 'load']);

        return parent::createParams($autoLoaders);
    }

    /**
     * @param $class
     * @return bool
     */
    public function isExistsClass($class)
    {
        return class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false);
    }

    /**
     * Load class
     *
     * @param  $class
     * @return bool
     * @throws Exception
     * @author dp <denis.a.shestakov@gmail.com>
     *
     * @version 2.0
     * @since   0.0
     */
    public function load($class)
    {
        if ($this->isExistsClass($class)) {
            return true;
        }

        if ($this->cache) {
            if ($fileName = $this->cache->get($class)) {
                include_once $fileName;
                return true;
            }
        }

        foreach ($this->getParam() as $autoLoader) {
            $fileName = null;

            if (!($autoLoader instanceof Closure) && ($autoLoader[0] instanceof ClassLoader)) {
                $fileName = $autoLoader[0]->findFile($class);

                if ($fileName && is_file($fileName)) {
                    include_once $fileName;
                } else {
                    continue;
                }
            } else {
                try {
                    $fileName = call_user_func($autoLoader, $class);
                } catch (Exception $e) {
                    continue;
                }
            }

            if ($this->isExistsClass($class)) {
                if ($fileName && is_string($fileName) && is_file($fileName) && $this->cache) {
                    $this->cache->set([$class => $fileName]);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @param $class
     * @return array|string|null
     * @throws Exception
     */
    public function autoload($class)
    {
        $fileName = $this->find(Module::PATH_SOURCE, $class, 'php');

        if ($fileName && is_file($fileName)) {
            include_once $fileName;

            if (!$this->isExistsClass($class)) {
                throw new Exception('File ' . $fileName . ' exists, but class ' . $class . ' not found');
            }

            return $fileName;
        }

        return null;
    }

    /**
     * @param $modulePathType
     * @param string|ClassObject $class
     * @param array $options
     * @param null $callback
     * @return array|string
     * @throws Exception
     */
    public function find($modulePathType, $class, $ext, array $options = [], $callback = null)
    {
        $options = array_merge(
            [
                'isRequired' => true,
                'isNotEmpty' => false,
                'isOnlyFirst' => false,
                'allMatchedPathes' => false
            ],
            $options
        );

        $fileName = null;

        $fullStackPathes = [];
        $matchedPathes = [];

        /** @var Application $ice */
        $ice = $this->getService('application');

        $classModule = $ice->getClassModule($class); // $class может не быть, а следственнои и $classModule (можно на нем и соновать проверку - !$classModule)

        $classModuleNamespace = $classModule->get('namespace');

        $classObject = ClassObject::create($class);

        $refNamespace = $classRefNamespace = $classObject->getClassRefNamespace($classModule);

        $className = $classObject->getName();

        $isDir = StringValue::create($class)->endsWith('\\');

        if ($isDir) {
            $options['allMatchedPathes'] = true;
            $className .= '\\';
            $refNamespace .= $className;
            $ext = $ext ? '\.' . $ext : '\.+';
        } else {
            $ext = '.' . $ext;
        }

        /** @var Module $module */
        $module = $ice->getParam('module');

        /** @var Module[] $modules */
        $modules = $options['isOnlyFirst']
            ? [$classModule ? $classModule : $module]
            : $module->getModules();

        foreach ($modules as $module) {
            $moduleNamespace = $module->get('namespace');

            $namespace = $isDir || $module === $classModule
                ? $classRefNamespace
                : 'vendors/' . $classModuleNamespace . $classRefNamespace;

            foreach (array_unique($module->getDir($modulePathType, true)) as $modulePathDir) {
                $findPath = $modulePathDir . str_replace(['_', '\\'], '/', $namespace . $className);

                if ($isDir) {
                    if (!is_dir($findPath)) {
                        continue;
                    }

                    $pattern = '/^' . preg_quote($findPath, '/') . '.*' . $ext . '$/i';

                    $directory = new \RecursiveDirectoryIterator($findPath);
                    $iterator = new \RecursiveIteratorIterator($directory);

                    $pathOffset = strlen($modulePathDir) + strlen($refNamespace);

                    foreach (new \RegexIterator($iterator, $pattern, \RecursiveRegexIterator::GET_MATCH) as $pathes) {
                        $path = reset($pathes);

                        $name = str_replace('/', '_', substr($path, $pathOffset, strrpos($path, '.') - $pathOffset));

                        $pathData = [
                            'module' => $module,
                            'path' => $path,
                            'class' => $moduleNamespace . $refNamespace . $name
                        ];

                        if ($callback) {
                            $pathData = call_user_func($callback, $pathData);
                        }

                        if ($pathData) {
                            $matchedPathes[] = $pathData;
                        }
                    }
                } else {
                    $findPath .= $ext;

                    $fullStackPathes[] = $findPath;

                    if (file_exists($findPath)) {
                        $matchedPathes[] = $findPath;

                        if (!$options['allMatchedPathes']) {
                            return $findPath;
                        }
                    }
                }
            }
        }

        if ($options['isRequired']) {
            if (!$options['allMatchedPathes'] || empty($matchedPathes)) {
                throw new \Exception('Files for class ' . $class . ' not found');
            }
        }

        if ($options['allMatchedPathes']) {
            return $matchedPathes;
        }

        return $options['isNotEmpty'] && !empty($fullStackPathes) ? reset($fullStackPathes) : '';
    }
}