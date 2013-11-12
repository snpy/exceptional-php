<?php

namespace OBV\Exceptional\Exception;

class PhpError extends PhpException
{
    /*
     * Must change the error message for undefined variables
     * Otherwise, Exceptional groups all errors together (regardless of variable name)
     */
    public function __construct($errstr, $errno, $errfile, $errline)
    {
        if (@substr($errstr, 0, 25) == 'Call to undefined method ') {
            $errstr = substr($errstr, 25) . ' is undefined';
        }
        parent::__construct($errstr, $errno, $errfile, $errline);
    }
}
