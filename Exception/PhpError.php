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
     * @param string $message
     * @param int    $severity
     * @param int    $filename
     * @param string $lineno
     */
    public function __construct($message, $severity, $filename, $lineno)
    {
        if (@substr($message, 0, 25) == 'Call to undefined method ') {
            $message = substr($message, 25) . ' is undefined';
        }
        parent::__construct($message, $severity, $filename, $lineno);
    }
}
