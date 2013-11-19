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
     * @param string $errstr
     * @param int    $errno
     * @param int    $errfile
     * @param string $errline
     */
    public function __construct($errstr, $errno, $errfile, $errline)
    {
        if (@substr($errstr, 0, 20) == 'Undefined variable: ') {
            $errstr = '$' . substr($errstr, 20) . ' is undefined';
        }
        parent::__construct($errstr, $errno, $errfile, $errline);
    }
}
