<?php

namespace OBV\Exceptional;

class Exceptional
{
    private static $exceptions;

    private static $previous_exception_handler;
    private static $previous_error_handler;

    private static $api_key;
    private static $use_ssl;

    private static $host = "plugin.getexceptional.com";
    private static $client_name = "exceptional-php";
    private static $version = "1.5";
    private static $protocol_version = 6;

    private static $controller;
    private static $action;
    private static $context;

    private static $blacklist = array();

    private static $proxy_host;
    private static $proxy_port;

    /*
     * Installs Exceptional as the default exception handler
     */
    public static function setup($api_key, $use_ssl = false)
    {
        if ($api_key == "") {
            $api_key = null;
        }

        self::$api_key = $api_key;
        self::$use_ssl = $use_ssl;

        self::$exceptions = array();
        self::$context    = array();
        self::$action     = "";
        self::$controller = "";

        // set exception handler & keep old exception handler around
        self::$previous_exception_handler = set_exception_handler(
            array("Exceptional", "handleException")
        );

        self::$previous_error_handler = set_error_handler(
            array("Exceptional", "handleError")
        );

        register_shutdown_function(
            array("Exceptional", "shutdown")
        );
    }

    public static function getApiKey()
    {
        return self::$api_key;
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
        return self::$client_name;
    }

    public static function getVersion()
    {
        return self::$version;
    }

    public static function getProtocolVersion()
    {
        return self::$protocol_version;
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
        return self::$proxy_host;
    }

    public static function getProxyPort()
    {
        return self::$proxy_port;
    }

    public static function blacklist($filters = array())
    {
        self::$blacklist = array_merge(self::$blacklist, $filters);
    }

    public static function shutdown()
    {
        if ($e = error_get_last()) {
            static::handleError($e["type"], $e["message"], $e["file"], $e["line"]);
        }
    }

    private static function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // this error code is not included in error_reporting
            return;
        }

        switch ($errno) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $ex = new PhpNotice($errstr, $errno, $errfile, $errline);
                break;

            case E_WARNING:
            case E_USER_WARNING:
                $ex = new PhpWarning($errstr, $errno, $errfile, $errline);
                break;

            case E_STRICT:
                $ex = new PhpStrict($errstr, $errno, $errfile, $errline);
                break;

            case E_PARSE:
                $ex = new PhpParse($errstr, $errno, $errfile, $errline);
                break;

            default:
                $ex = new PhpError($errstr, $errno, $errfile, $errline);
        }

        static::handleException($ex, false);

        if (self::$previous_error_handler) {
            call_user_func(self::$previous_error_handler, $errno, $errstr, $errfile, $errline);
        }
    }

    /*
     * Exception handle class. Pushes the current exception onto the exception
     * stack and calls the previous handler, if it exists. Ensures seamless
     * integration.
     */
    private static function handleException($exception, $call_previous = true)
    {
        self::$exceptions[] = $exception;

        if (Exceptional::$api_key != null) {
            $data = new ExceptionalData($exception);
            ExceptionalRemote::sendException($data);
        }

        // if there's a previous exception handler, we call that as well
        if ($call_previous && self::$previous_exception_handler) {
            call_user_func(self::$previous_exception_handler, $exception);
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
        self::$proxy_host = $host;
        self::$proxy_port = $port;
    }
}

class Http404Error extends Exception
{
    public function __construct()
    {
        if (!isset($_SERVER["HTTP_HOST"])) {
            echo "Run PHP on a server to use Http404Error.\n";
            exit(0);
        }
        parent::__construct($_SERVER["REQUEST_URI"] . " can't be found.");
    }
}
