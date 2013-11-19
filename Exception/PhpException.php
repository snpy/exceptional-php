<?php

namespace OBV\Component\Exceptional\Exception;

use ErrorException;

/**
 * Class PhpException
 *
 * @package OBV\Component\Exceptional\Exception
 */
class PhpException extends ErrorException
{
    /**
     * PhpException c-tor
     *
     * @param string $message
     * @param int    $severity
     * @param int    $filename
     * @param string $lineno
     */
    public function __construct($message, $severity, $filename, $lineno)
    {
        parent::__construct($message, 0, $severity, $filename, $lineno);
    }
}
