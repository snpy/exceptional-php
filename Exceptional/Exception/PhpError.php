<?php

namespace OBV\Component\Exceptional\Exception;

/**
 * Class PhpError
 *
 * @package OBV\Component\Exceptional\Exception
 */
class PhpError extends PhpException
{
    /**
     * PhpError c-tor
     *
     * @param string $errstr
     * @param int    $errno
     * @param int    $errfile
     * @param string $errline
     */
    public function __construct($errstr, $errno, $errfile, $errline)
    {
        if (@substr($errstr, 0, 25) == 'Call to undefined method ') {
            $errstr = substr($errstr, 25) . ' is undefined';
        }
        parent::__construct($errstr, $errno, $errfile, $errline);
    }
}
