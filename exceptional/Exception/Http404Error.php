<?php

namespace OBV\Exceptional\Exception;

use Exception;

class Http404Error extends Exception
{
    public function __construct()
    {
        if (!isset($_SERVER["HTTP_HOST"])) {
            echo "Run PHP on a server to use Http404Error.\n";
            exit(0);
        }
        parent::__construct($_SERVER["REQUEST_URI"] . " can't be found.");
    }
}
