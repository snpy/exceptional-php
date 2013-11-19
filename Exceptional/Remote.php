<?php

namespace OBV\Exceptional;

class Remote
{
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
        $compressed = gzencode($exception->toJson());

        return array($url, $compressed);
    }

    private static function postponeRemoteCall($url, $data)
    {
        Logger::log(Encoder::encode($url, $data));
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
        $secure = Exceptional::getUseSsl();
        $host   = Exceptional::getProxyHost() ? : Exceptional::getHost();

        $secure && ($host = 'ssl://' . $host);

        $port    = Exceptional::getProxyPort() ? : ($secure ? 443 : 80);
        $timeout = $secure ? 4 : 2;

        $socket = fsockopen($host, $port, $errorNumber, $errorString, $timeout);
        if ($socket) {
            $protocol = $secure ? 'https' : 'http';

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
