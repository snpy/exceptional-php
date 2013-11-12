<?php

namespace OBV\Exceptional\Exception;

use ErrorException;

class PhpException extends ErrorException
{
    public function __construct($errstr, $errno, $errfile, $errline)
    {
        parent::__construct($errstr, 0, $errno, $errfile, $errline);
    }
}
