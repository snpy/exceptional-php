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

    private static function preparePostData(Exception $exception)
    {
        $uniqueness_hash = $exception->uniquenessHash();
        $hash_param      = ($uniqueness_hash) ? null : "&hash={$uniqueness_hash}";
        $url             = "/api/errors?api_key=" . Exceptional::getApiKey() . "&protocol_version=" . Exceptional::getProtocolVersion() . $hash_param;
        $compressed      = gzencode($exception->toJson(), 1);

        return array($url, $compressed);
    }

    /*
     * Sends a POST request
     */
    private static function callRemote($path, $post_data)
    {
        $default_port = Exceptional::getUseSsl() ? 443 : 80;

        $host = Exceptional::getProxyHost() ? : Exceptional::getHost();
        $port = Exceptional::getProxyPort() ? : $default_port;

        if (Exceptional::getUseSsl() === true) {
            $s        = fsockopen("ssl://" . $host, $port, $errno, $errstr, 4);
            $protocol = "https";
        } else {
            $s        = fsockopen($host, $port, $errno, $errstr, 2);
            $protocol = "http";
        }

        if (!$s) {
            echo "[Error $errno] $errstr\n";

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

        fwrite($s, $request);

        $response = "";
        while (!feof($s)) {
            $response .= fgets($s);
        }

        fclose($s);

        return true;
    }
}
