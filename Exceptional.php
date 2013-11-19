<?php

namespace OBV\Component\Exceptional;

use OBV\Component\Exceptional\Exception\PhpException;
use RuntimeException;

/**
 * Class Exceptional
 *
 * @package OBV\Component\Exceptional
 */
class Exceptional
{
    /** @var array */
    private static $exceptions = array();

    /** @var callback|null */
    private static $previousExceptionHandler;

    /** @var callback|null */
    private static $previousErrorHandler;

    /** @var string */
    private static $apiKey;

    /** @var bool */
    private static $useSsl;

    /** @var string */
    private static $host = 'plugin.getexceptional.com';

    /** @var string */
    private static $clientName = 'exceptional-php';

    /** @var string */
    private static $version = '1.5';

    /** @var int */
    private static $protocolVersion = 6;

    /** @var string */
    private static $controller = '';

    /** @var string */
    private static $action = '';

    /** @var array */
    private static $context = array();

    /** @var array */
    private static $blacklist = array();

    /** @var string|null */
    private static $proxyHost;

    /** @var int|null */
    private static $proxyPort;

    /** @var string|null */
    private static $logDirectory;

    /** @var bool */
    private static $registered = false;

    /**
     * Setup Exceptional system
     *
     * @param string      $apiKey
     * @param bool        $useSsl
     * @param string|null $logDirectory
     *
     * @throws RuntimeException
     */
    final public static function setup($apiKey, $useSsl = false, $logDirectory = null)
    {
        if (self::$registered) {
            if ($apiKey !== self::$apiKey || $useSsl !== self::$useSsl) {
                throw new RuntimeException('Exceptional system can be initiated only once');
            }

            return;
        }

        self::$apiKey = empty($apiKey) ? null : $apiKey;
        self::$useSsl = $useSsl;

        self::$previousExceptionHandler = set_exception_handler(array(__CLASS__, 'handleException'));
        self::$previousErrorHandler = set_error_handler(array(__CLASS__, 'handleError'));
        register_shutdown_function(array(__CLASS__, 'shutdown'));

        self::$registered = true;

        $logDirectory && static::setLogDirectory($logDirectory);
    }

    /**
     * Get API key
     *
     * @return string
     */
    public static function getApiKey()
    {
        return self::$apiKey;
    }

    /**
     * Get if we should SSL
     *
     * @return bool
     */
    public static function getUseSsl()
    {
        return self::$useSsl;
    }

    /**
     * Get hostname
     *
     * @return string
     */
    public static function getHost()
    {
        return self::$host;
    }

    /**
     * Get web browser name
     *
     * @return string
     */
    public static function getClientName()
    {
        return self::$clientName;
    }

    /**
     * Get Exceptional version number
     *
     * @return string
     */
    public static function getVersion()
    {
        return self::$version;
    }

    /**
     * Get protocol version number
     *
     * @return int
     */
    public static function getProtocolVersion()
    {
        return self::$protocolVersion;
    }

    /**
     * Get controller name
     *
     * @return string
     */
    public static function getController()
    {
        return self::$controller;
    }

    /**
     * Set controller name
     *
     * @param $controller
     */
    public static function setController($controller)
    {
        self::$controller = $controller;
    }

    /**
     * Get action name
     *
     * @return string
     */
    public static function getAction()
    {
        return self::$action;
    }

    /**
     * Set action name
     *
     * @param $action
     */
    public static function setAction($action)
    {
        self::$action = $action;
    }

    /**
     * Get context
     *
     * @return array
     */
    public static function getContext()
    {
        return self::$context;
    }

    /**
     * Get blacklist
     *
     * @return array
     */
    public static function getBlackList()
    {
        return self::$blacklist;
    }

    /**
     * Get proxy hostname
     *
     * @return null|string
     */
    public static function getProxyHost()
    {
        return self::$proxyHost;
    }

    /**
     * Get proxy port
     *
     * @return int|null
     */
    public static function getProxyPort()
    {
        return self::$proxyPort;
    }

    /**
     * Set log directory
     *
     * @param string|null $logDirectory
     */
    public static function setLogDirectory($logDirectory)
    {
        static::$logDirectory = ltrim($logDirectory, '/\\');
    }

    /**
     * Get log directory
     *
     * @return null|string
     */
    public static function getLogDirectory()
    {
        return static::$logDirectory;
    }

    /**
     * Update blacklist array
     *
     * @param array $filters
     */
    public static function blacklist(array $filters = array())
    {
        self::$blacklist = array_merge(self::$blacklist, $filters);
    }

    /**
     * Shutdown callback
     */
    public static function shutdown()
    {
        if ($error = error_get_last()) {
            static::handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * Create exception based on error
     *
     * @param string $message
     * @param int    $severity
     * @param string $filename
     * @param int    $lineno
     *
     * @return PhpException
     */
    private static function errorToException($message, $severity, $filename, $lineno)
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
        $class = '\OBV\Component\Exceptional\Exception\\' . (isset($map[$severity]) ? $map[$severity] : 'PhpError');

        return new $class($message, $severity, $filename, $lineno);
    }

    public static function handleError($severity, $message, $filename, $lineno)
    {
        if (!(error_reporting() & $severity)) {
            return;
        }

        static::handleException(static::errorToException($message, $severity, $filename, $lineno), false);

        if (self::$previousErrorHandler) {
            call_user_func(self::$previousErrorHandler, $severity, $message, $filename, $lineno);
        }
    }

    public static function handleException($exception, $callPrevious = true)
    {
        self::$exceptions[] = $exception;

        Exceptional::$apiKey && Remote::sendException(new Data($exception));

        $callPrevious && self::$previousExceptionHandler && call_user_func(self::$previousExceptionHandler, $exception);
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
