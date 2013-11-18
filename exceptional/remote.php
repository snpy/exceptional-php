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
        $logFile = '/path/to/log/file';
        $logRow  = static::encode($url, $data);
        file_put_contents($logFile, $logRow, FILE_APPEND);
    }

    private static function encode($url, $compressed)
    {
        return $url . "\x00" . $compressed;
    }

    public static function decode($data)
    {
        return explode("\x00", $data, 2);
    }

    /*
     * Sends a POST request
     */
    private static function callRemote($path, $postData)
    {
        $defaultPort = Exceptional::getUseSsl() ? 443 : 80;

        $host = Exceptional::getProxyHost() ? : Exceptional::getHost();
        $port = Exceptional::getProxyPort() ? : $defaultPort;

        if (Exceptional::getUseSsl() === true) {
            $socket   = fsockopen('ssl://' . $host, $port, $errorNumber, $errorString, 4);
            $protocol = 'https';
        } else {
            $socket   = fsockopen($host, $port, $errorNumber, $errorString, 2);
            $protocol = 'http';
        }

        if (!$socket) {
            printf('[Error %s] %s%s', $errorNumber, $errorString, PHP_EOL);

            return false;
        }

        $url = $protocol . '://' . Exceptional::getHost() . $path;
        $eol = "\r\n";

        $request = sprintf('POST %s HTTP/1.1', $url) . $eol;
        $request .= 'Host: ' . Exceptional::getHost() . $eol;
        $request .= 'Accept: */*' . $eol;
        $request .= sprintf('User-Agent: %s %s', Exceptional::getClientName(), Exceptional::getVersion()) . $eol;
        $request .= 'Content-Type: text/json' . $eol;
        $request .= 'Connection: close' . $eol;
        $request .= 'Content-Length: ' . strlen($postData) . $eol . $eol;
        $request .= $postData . $eol;

        fwrite($socket, $request);

        $response = '';
        while (!feof($socket)) {
            $response .= fgets($socket);
        }

        fclose($socket);

        return true;
    }
}
