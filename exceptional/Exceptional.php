<?php

namespace OBV\Exceptional;

use OBV\Exceptional\Exception as Error;

class Exceptional
{
    private static $exceptions;

    private static $previousExceptionHandler;
    private static $previousErrorHandler;

    private static $apiKey;
    private static $use_ssl;

    private static $host = 'plugin.getexceptional.com';
    private static $clientName = 'exceptional-php';
    private static $version = '1.5';
    private static $protocolVersion = 6;

    private static $controller;
    private static $action;
    private static $context;

    private static $blacklist = array();

    private static $proxyHost;
    private static $proxyPort;

    /*
     * Installs Exceptional as the default exception handler
     */
    public static function setup($apiKey, $useSsl = false)
    {
        if ($apiKey == '') {
            $apiKey = null;
        }

        self::$apiKey = $apiKey;
        self::$use_ssl = $useSsl;

        self::$exceptions = array();
        self::$context    = array();
        self::$action     = '';
        self::$controller = '';

        // set exception handler & keep old exception handler around
        self::$previousExceptionHandler = set_exception_handler(
            array('Exceptional', 'handleException')
        );

        self::$previousErrorHandler = set_error_handler(
            array('Exceptional', 'handleError')
        );

        register_shutdown_function(
            array('Exceptional', 'shutdown')
        );
    }

    public static function getApiKey()
    {
        return self::$apiKey;
    }

    public static function getUseSsl()
    {
        return self::$use_ssl;
    }

    public static function getHost()
    {
        return self::$host;
    }

    public static function getClientName()
    {
        return self::$clientName;
    }

    public static function getVersion()
    {
        return self::$version;
    }

    public static function getProtocolVersion()
    {
        return self::$protocolVersion;
    }

    public static function getController()
    {
        return self::$controller;
    }

    public static function setController($controller)
    {
        self::$controller = $controller;
    }

    public static function getAction()
    {
        return self::$action;
    }

    public static function setAction($action)
    {
        self::$action = $action;
    }

    public static function getContext()
    {
        return self::$context;
    }

    public static function getBlackList()
    {
        return self::$blacklist;
    }

    public static function getProxyHost()
    {
        return self::$proxyHost;
    }

    public static function getProxyPort()
    {
        return self::$proxyPort;
    }

    public static function blacklist($filters = array())
    {
        self::$blacklist = array_merge(self::$blacklist, $filters);
    }

    public static function shutdown()
    {
        if ($error = error_get_last()) {
            static::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    private static function errorToException($errno, $errstr, $errfile, $errline)
    {
        $map = array(
            E_NOTICE          => 'PhpNotice',
            E_USER_NOTICE     => 'PhpNotice',
            E_DEPRECATED      => 'PhpDeprecated',
            E_USER_DEPRECATED => 'PhpDeprecated',
            E_WARNING         => 'PhpWarning',
            E_USER_WARNING    => 'PhpWarning',
            E_STRICT          => 'PhpStrict',
            E_PARSE           => 'PhpParse',
        );
        $class = '\OBV\Exceptional\Exception\\' . (isset($map[$errno]) ? $map[$errno] : 'PhpError');

        return new $class($errno, $errstr, $errfile, $errline);
    }

    private static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return;
        }

        static::handleException(static::errorToException($errno, $errstr, $errfile, $errline), false);

        self::$previousErrorHandler && call_user_func(self::$previousErrorHandler, $errno, $errstr, $errfile, $errline);
    }

    /*
     * Exception handle class. Pushes the current exception onto the exception
     * stack and calls the previous handler, if it exists. Ensures seamless
     * integration.
     */
    private static function handleException($exception, $call_previous = true)
    {
        self::$exceptions[] = $exception;

        if (Exceptional::$apiKey != null) {
            $data = new Data($exception);
            Remote::sendException($data);
        }

        // if there's a previous exception handler, we call that as well
        if ($call_previous && self::$previousExceptionHandler) {
            call_user_func(self::$previousExceptionHandler, $exception);
        }
    }

    public static function context($data = array())
    {
        self::$context = array_merge(self::$context, $data);
    }

    public static function clear()
    {
        self::$context = array();
    }

    public static function proxy($host, $port)
    {
        self::$proxyHost = $host;
        self::$proxyPort = $port;
    }
}
