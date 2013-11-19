<?php

namespace OBV\Exceptional;

/**
 * @file
 * @since  18.11.13 22:41 GMT+2
 * @author GG Team <gg@team.tld>
 */

/**
 * Class Encoder
 *
 * @package OBV\Exceptional
 */
class Encoder
{
    /**
     * Encode URL and data into one string
     *
     * @param string $url
     * @param string $compressedData
     *
     * @return string
     */
    public static function encode($url, $compressedData)
    {
        return base64_encode($url . "\x00" . $compressedData);
    }

    /**
     * Decode string into array
     *
     * @param string $data
     *
     * @return array Array with $url and $compressedData values.
     */
    public static function decode($data)
    {
        return explode("\x00", base64_decode($data), 2);
    }
}
