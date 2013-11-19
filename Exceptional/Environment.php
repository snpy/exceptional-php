<?php

namespace OBV\Exceptional;

/**
 * Class Environment
 *
 * @package OBV\Exceptional
 */
class Environment
{
    /** @var array */
    private static $environment;

    /**
     * Get environment array
     *
     * @return array
     */
    public static function toArray()
    {
        if (!self::$environment) {
            $env = $_SERVER;

            // remove the following $_SERVER variables
            $vars = array(
                'PHPSELF',
                'SCRIPT_NAME',
                'SCRIPT_FILENAME',
                'PATH_TRANSLATED',
                'DOCUMENT_ROOT',
                'PHP_SELF',
                'argv',
                'argc',
                'REQUEST_TIME',
                'PHP_AUTH_PW'
            );
            foreach ($vars as $var) {
                if (isset($env[$var])) {
                    unset($env[$var]);
                }
            }

            // remove variables that begin with HTTP_
            foreach ($env as $k => $v) {
                if (substr($k, 0, 5) == 'HTTP_') {
                    unset($env[$k]);
                }
            }

            self::$environment = array(
                'client'                  => array(
                    'name'             => Exceptional::getClientName(),
                    'version'          => Exceptional::getVersion(),
                    'protocol_version' => Exceptional::getProtocolVersion()
                ),
                'application_environment' => array(
                    'environment'                => 'production',
                    'env'                        => $env,
                    'host'                       => php_uname('n'),
                    'run_as_user'                => static::getUsername(),
                    'application_root_directory' => static::getRootDir(),
                    'language'                   => 'php',
                    'language_version'           => phpversion(),
                    'framework'                  => null,
                    'libraries_loaded'           => array()
                )
            );
        }

        return self::$environment;
    }

    /**
     * Get username
     *
     * @return string
     */
    private static function getUsername()
    {
        $vars = array('LOGNAME', 'USER', 'USERNAME', 'APACHE_RUN_USER');
        foreach ($vars as $var) {
            if (getenv($var)) {
                return getenv($var);
            }
        }

        return 'UNKNOWN';
    }

    /**
     * Get root directory
     *
     * @return string
     */
    private static function getRootDir()
    {
        if (isset($_SERVER['PWD'])) {
            return $_SERVER['PWD'];
        }

        return @$_SERVER['DOCUMENT_ROOT'];
    }
}
