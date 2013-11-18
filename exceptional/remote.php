<?php

namespace OBV\Exceptional;

class Remote
{
    /*
     * Does the actual sending of an exception
     */
    public static function sendException($exception)
    {
        list($url, $data) = static::preparePostData($exception);

        $level = error_reporting(0);
        if (!static::callRemote($url, $data)) {
            static::postponeRemoteCall($url, $data);
        }
        error_reporting($level);
    }

    private static function preparePostData(Data $exception)
    {
        $hash       = $exception->uniquenessHash();
        $hashParam  = $hash ? ('&hash=' . $hash) : '';
        $url        = '/api/errors?api_key=%s&protocol_version=%s%s';
        $url        = sprintf($url, Exceptional::getApiKey(), Exceptional::getProtocolVersion(), $hashParam);
        $compressed = gzencode($exception->toJson(), 1);

        return array($url, $compressed);
    }

    private static function postponeRemoteCall($url, $data)
    {
        Logger::log(static::encode($url, $data));
    }

    public static function encode($url, $compressed)
    {
        return base64_encode($url . "\x00" . $compressed);
    }

    public static function decode($data)
    {
        return explode("\x00", base64_decode($data), 2);
    }

    public static function callRemote($path, $postData)
    {
        if (!$socket = static::openSocket($protocol)) {
            return false;
        }

        $url = $protocol . '://' . Exceptional::getHost() . $path;

        fwrite($socket, static::createRequest($url, $postData));

        $response = '';
        while (!feof($socket)) {
            $response .= fgets($socket);
        }

        fclose($socket);

        return true;
    }

    private static function openSocket(&$protocol = null)
    {
        $host = Exceptional::getProxyHost() ? : Exceptional::getHost();
        $port = Exceptional::getProxyPort() ? : (Exceptional::getUseSsl() ? 443 : 80);

        if (Exceptional::getUseSsl() === true) {
            $socket   = fsockopen('ssl://' . $host, $port, $errorNumber, $errorString, 4);
            $protocol = 'https';
        } else {
            $socket   = fsockopen($host, $port, $errorNumber, $errorString, 2);
            $protocol = 'http';
        }

        if ($socket) {
            return $socket;
        }

        printf('[Error %s] %s%s', $errorNumber, $errorString, PHP_EOL);

        return null;
    }

    private static function createRequest($url, $postData)
    {
        $eol = "\r\n";

        $request = sprintf('POST %s HTTP/1.1', $url) . $eol;
        $request .= 'Host: ' . Exceptional::getHost() . $eol;
        $request .= 'Accept: */*' . $eol;
        $request .= sprintf('User-Agent: %s %s', Exceptional::getClientName(), Exceptional::getVersion()) . $eol;
        $request .= 'Content-Type: text/json' . $eol;
        $request .= 'Connection: close' . $eol;
        $request .= 'Content-Length: ' . strlen($postData) . $eol . $eol;
        $request .= $postData . $eol;

        return $request;
    }
}
