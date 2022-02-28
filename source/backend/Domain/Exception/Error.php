<?php

namespace Ifacesoft\Ice\Core\Domain\Exception;

use Exception;
use Ice\Helper\Directory;
use Ice\Helper\Transliterator;
use Ice\Helper\Type_String;
use Ifacesoft\Ice\Core\Domain\Value\StringValue;
use Ifacesoft\Ice\Core\Domain\Value\ValueObject;
use RuntimeException;
use Throwable;

class Error extends RuntimeException
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $context;

    final private function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $logDir = getcwd() . '/../var/log/' . date('Y-m-d_H') . '/';

        if (!is_dir($logDir) && !mkdir($logDir, 0777, true)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
        }

        $logFile = date('i') . '_' . StringValue::create($this->getMessage())->truncate(250, '', StringValue::RETURN_TYPE_OBJECT)->transliterate() . '.log';

        file_put_contents($logDir . $logFile, $this->get(), FILE_APPEND);
    }

    public function get($type = 'file', $level = 0)
    {
        return self::error($this, $type, $level) . "\n";
    }

    private static function error($e, $type = 'file', $level = 0)
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

        $method = method_exists($e, 'getMethod') ? $e->getMethod() . ': ' : '';

        $message = $method . ': ' . str_replace('.php on line ', '.php:', $e->getMessage()) . ' ';

        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI']: $_SERVER['argv'][1];

        $string = $level
            ? ''
            : '[' . \date('H:i:s') . ' ' . microtime(true) . '] ' . $requestUri . $new;

        $offset = str_repeat($tab, $level);

        $string .= $offset . $yellow . $black . ' ' . self::class . ': ' . $red . $message . $reset . $new;

        $string .= $offset . $cyan . $black . ' ' . $e->getFile() . ':' . $e->getLine() . ' ' . $reset . $new;

        if ($e instanceof Error && $context = $e->getContext()) {
            try {
                $string .= $new . $blue . $black . ValueObject::create($context)->varExport() . $reset . $new;
            } catch (Exception $e) {
                $string .= $new . $blue . $black . ValueObject::create($context)->printR() . $reset . $new;
            } catch (Throwable $e) {
                $string .= $new . $blue . $black . ValueObject::create($context)->printR() . $reset . $new;
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
//                   $moduleDir = $e->get('dir');
            $moduleDir = '/var/www/html/';
            // todo: Строчки - файлы проекта помечать жирным

            $string .= '<pre>' . str_replace($moduleDir, './', $stackTrace) . '</pre>';
        }

        /** @var Error $e */
        if ($e = $e->getPrevious()) {
            $string .= self::error($e, $type, $level + 1);
        }

        return $string;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        self::create(__METHOD__, $errstr, ['code' => $errno, 'file' => $errfile, 'line' => $errline]);
    }

    /**
     * @param $method
     * @param $message
     * @param $context
     * @param null $previous
     * @return static
     */
    public static function create($method, $message = 'exception', $context = [], $previous = null)
    {
        $exception = new static($message, 0, $previous);

        $exception->method = $method;
        $exception->context = (array)$context;

        return $exception;
    }

    public function throw()
    {
        throw $this;
    }
}
