<?php

namespace OBV\Exceptional\Exception;

class PhpNotice extends PhpException
{
    /*
     * Must change the error message for undefined variables
     * Otherwise, Exceptional groups all errors together (regardless of variable name)
     */
    public function __construct($errstr, $errno, $errfile, $errline)
    {
        if (@substr($errstr, 0, 20) == 'Undefined variable: ') {
            $errstr = '$' . substr($errstr, 20) . ' is undefined';
        }
        parent::__construct($errstr, $errno, $errfile, $errline);
    }
}
