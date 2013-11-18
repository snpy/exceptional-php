<?php

namespace OBV\Exceptional;

/**
 * @file
 * @since  18.11.13 22:24 GMT+2
 * @author GG Team <gg@team.tld>
 */

/**
 * Class Logger
 *
 * @package OBV\Exceptional
 */
class Logger
{
    public static function log($data)
    {
        if ($directory = Exceptional::getLogDirectory()) {
            file_put_contents(tempnam($directory, 'eio-'), Remote::encode($url, $data), FILE_APPEND);
        }
    }

    public static function getLogFiles()
    {
        return ($directory = Exceptional::getLogDirectory()) ? glob($directory . '/eio-*') : array();
    }
}
