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
        $logFile = tempnam(Exceptional::getLogDirectory(), 'eio-');

        file_put_contents($logFile, static::encode($url, $data), FILE_APPEND);
    }

    public static function getLogFiles()
    {
        return glob(Exceptional::getLogDirectory() . '/eio-*');
    }
}
