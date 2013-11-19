<?php

namespace OBV\Component\Exceptional;

/**
 * @file
 * @since  18.11.13 22:24 GMT+2
 * @author GG Team <gg@team.tld>
 */

/**
 * Class Logger
 *
 * @package OBV\Component\Exceptional
 */
class Logger
{
    /**
     * Log compressed data
     *
     * @param string $data
     */
    public static function log($data)
    {
        if ($directory = Exceptional::getLogDirectory()) {
            file_put_contents(tempnam($directory, 'eio-'), $data, FILE_APPEND);
        }
    }

    /**
     * Find all postponed log reports
     *
     * @return array
     */
    public static function getLogFiles()
    {
        return ($directory = Exceptional::getLogDirectory()) ? glob($directory . '/eio-*') : array();
    }
}
