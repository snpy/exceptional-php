<?php

namespace OBV\Exceptional\Exception;

use ErrorException;

/**
 * Class PhpException
 *
 * @package OBV\Exceptional\Exception
 */
class PhpException extends ErrorException
{
    /**
     * PhpException c-tor
     *
     * @param string $errstr
     * @param int    $errno
     * @param int    $errfile
     * @param string $errline
     */
    public function __construct($errstr, $errno, $errfile, $errline)
    {
        parent::__construct($errstr, 0, $errno, $errfile, $errline);
    }
}
