<?php

namespace OBV\Exceptional\Exception;

use Exception;

/**
 * Class Http404Error
 *
 * @package OBV\Exceptional\Exception
 */
class Http404Error extends Exception
{
    /**
     * Http404Error c-tor
     */
    public function __construct()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            throw new \RuntimeException('Run PHP on a server to use \OBV\Exceptional\Exception\Http404Error.');
        }
        parent::__construct($_SERVER['REQUEST_URI'] . ' can\'t be found.');
    }
}
