<?php

class ExceptionalRemote
{
    /*
     * Does the actual sending of an exception
     */
    public static function sendException($exception)
    {
        list($url, $data) = static::preparePostData($exception);

        static::callRemote($url, $data);
    }

    private static function preparePostData(ExceptionalData $exception)
    {
        $hash       = $exception->uniquenessHash();
        $hashParam  = $hash ? '' : ('&hash=' . $hash);
        $url        = '/api/errors?api_key=%s&protocol_version=%s%s';
        $url        = sprintf($url, Exceptional::getApiKey(), Exceptional::getProtocolVersion(), $hashParam);
        $compressed = gzencode($exception->toJson(), 1);

        return array($url, $compressed);
    }

    /*
     * Sends a POST request
     */
    private static function callRemote($path, $post_data)
    {
        $defaultPort = Exceptional::getUseSsl() ? 443 : 80;

        $host = Exceptional::getProxyHost() ? : Exceptional::getHost();
        $port = Exceptional::getProxyPort() ? : $defaultPort;

        if (Exceptional::getUseSsl() === true) {
            $socket   = fsockopen("ssl://" . $host, $port, $errorNumber, $errorString, 4);
            $protocol = "https";
        } else {
            $socket   = fsockopen($host, $port, $errorNumber, $errorString, 2);
            $protocol = "http";
        }

        if (!$socket) {
            echo "[Error $errorNumber] $errorString\n";

            return false;
        }

        $url = "$protocol://" . Exceptional::getHost() . "$path";

        $request = "POST $url HTTP/1.1\r\n";
        $request .= "Host: " . Exceptional::getHost() . "\r\n";
        $request .= "Accept: */*\r\n";
        $request .= "User-Agent: " . Exceptional::getClientName() . " " . Exceptional::getVersion() . "\r\n";
        $request .= "Content-Type: text/json\r\n";
        $request .= "Connection: close\r\n";
        $request .= "Content-Length: " . strlen($post_data) . "\r\n\r\n";
        $request .= "$post_data\r\n";

        fwrite($socket, $request);

        $response = "";
        while (!feof($socket)) {
            $response .= fgets($socket);
        }

        fclose($socket);

        return true;
    }
}
