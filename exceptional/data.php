<?php

namespace OBV\Exceptional;

use Exception;
use OBV\Exceptional\Exception\Http404Error;

class Data
{
    private $exception;
    private $data;
    private $backtrace = array();

    public function __construct(Exception $exception)
    {
        $this->exception = $exception;

        $trace = $this->exception->getTrace();
        foreach ($trace as $info) {
            if (isset($info['file'])) {
                $this->backtrace[] = sprintf('%s:%s:in `%s`', $info['file'], $info['line'], $info['function']);
            }
        }

        // environment data
        $data = Environment::toArray();

        // exception data
        $message = $this->exception->getMessage();
        $now     = gmdate('c');

        // spoof 404 error
        $errorClass = $this->exception instanceof Http404Error
            ? 'ActionController::UnknownAction'
            : get_class($this->exception);

        $data['exception'] = array(
            'exception_class' => $errorClass,
            'message'         => $message,
            'backtrace'       => $this->backtrace,
            'occurred_at'     => $now
        );

        // context
        $context = Exceptional::getContext();
        if (!empty($context)) {
            $data['context'] = $context;
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            // request data
            $session = isset($_SESSION) ? $_SESSION : array();

            // sanitize headers
            $headers = $this->getAllHeaders();
            if (isset($headers['Cookie'])) {
                $sessionKey        = preg_quote(ini_get('session.name'), '/');
                $searchFor         = '/' . $sessionKey . '=\S+/';
                $replaceWith       = $sessionKey . '=[FILTERED]';
                $headers['Cookie'] = preg_replace($searchFor, $replaceWith, $headers['Cookie']);
            }

            $server = $_SERVER;
            $keys   = array('HTTPS', 'HTTP_HOST', 'REQUEST_URI', 'REQUEST_METHOD', 'REMOTE_ADDR');
            $this->fillKeys($server, $keys);

            $protocol = $server['HTTPS'] && $server['HTTPS'] != 'off' ? 'https://' : 'http://';
            $url      = $server['HTTP_HOST'] ? ($protocol . $server['HTTP_HOST'] . $server['REQUEST_URI']) : '';

            $data['request'] = array(
                'url'            => $url,
                'request_method' => strtolower($server['REQUEST_METHOD']),
                'remote_ip'      => $server['REMOTE_ADDR'],
                'headers'        => $headers,
                'session'        => $session
            );

            $params = array_merge($_GET, $_POST);

            foreach (Exceptional::getBlackList() as $filter) {
                $params = $this->filterParams($params, $filter);
            }

            if (!empty($params)) {
                $data['request']['parameters'] = $params;
            }
        } else {
            $data['request'] = array();
        }

        $data['request']['controller'] = Exceptional::getController();
        $data['request']['action']     = Exceptional::getAction();

        $this->data = $data;
    }

    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(
                    ' ',
                    '-',
                    ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                )] = $value;
            }
        }

        return $headers;
    }

    public function uniquenessHash()
    {
        return md5(implode('', $this->backtrace));
    }

    public function toJson()
    {
        return json_encode($this->data);
    }

    public function getData()
    {
        return $this->data;
    }

    private function fillKeys(&$arr, $keys)
    {
        foreach ($keys as $key) {
            if (!isset($arr[$key])) {
                $arr[$key] = false;
            }
        }
    }

    private function filterParams($params, $term)
    {
        foreach ($params as $key => $value) {
            if (preg_match('/' . $term. '/i', $key)) {
                $params[$key] = '[FILTERED]';
            } elseif (is_array($value)) {
                $params[$key] = $this->filterParams($value, $term);
            }
        }

        return $params;
    }
}
