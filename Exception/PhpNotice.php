<?php

namespace OBV\Component\Exceptional\Exception;

/**
 * Class PhpNotice
 *
 * @package OBV\Component\Exceptional\Exception
 */
class PhpNotice extends PhpException
{
    /**
     * PhpNotice c-tor
     *
     * @param string $message
     * @param int    $severity
     * @param int    $filename
     * @param string $lineno
     */
    public function __construct($message, $severity, $filename, $lineno)
    {
        if (@substr($message, 0, 20) == 'Undefined variable: ') {
            $message = '$' . substr($message, 20) . ' is undefined';
        }
        parent::__construct($message, $severity, $filename, $lineno);
    }
}
