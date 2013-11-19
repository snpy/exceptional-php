<?php

namespace OBV\Component\Exceptional;

use Exception;
use OBV\Component\Exceptional\Exception\Http404Error;

/**
 * Class Data
 *
 * @package OBV\Component\Exceptional
 */
class Data
{
    /** @var Exception */
    private $exception;

    /** @var array */
    private $data;

    /** @var array */
    private $backtrace = array();

    /**
     * Data c-tor
     *
     * @param Exception $exception
     */
    public function __construct(Exception $exception)
    {
        $this->exception = $exception;

        $trace = $this->exception->getTrace();
        foreach ($trace as $info) {
            if (isset($info['file'])) {
                $this->backtrace[] = sprintf('%s:%s:in `%s`', $info['file'], $info['line'], $info['function']);
            }
        }

        $data = Environment::toArray();

        $message = $this->exception->getMessage();
        $now     = gmdate('c');

        $errorClass = $this->exception instanceof Http404Error
            ? 'ActionController::UnknownAction'
            : get_class($this->exception);

        $data['exception'] = array(
            'exception_class' => $errorClass,
            'message'         => $message,
            'backtrace'       => $this->backtrace,
            'occurred_at'     => $now
        );

        $context = Exceptional::getContext();
        if (!empty($context)) {
            $data['context'] = $context;
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $session = isset($_SESSION) ? $_SESSION : array();

            $headers = $this->getAllHeaders();
            if (isset($headers['Cookie'])) {
                $sessionKey        = preg_quote(ini_get('session.name'), '/');
                $searchFor         = '/' . $sessionKey . '=\S+/';
                $replaceWith       = $sessionKey . '=[FILTERED]';
                $headers['Cookie'] = preg_replace($searchFor, $replaceWith, $headers['Cookie']);
            }

            $keys   = array('HTTPS', 'HTTP_HOST', 'REQUEST_URI', 'REQUEST_METHOD', 'REMOTE_ADDR');
            $server = $this->fillKeys($_SERVER, $keys);

            $protocol = $server['HTTPS'] && $server['HTTPS'] != 'off' ? 'https://' : 'http://';
            $url      = $server['HTTP_HOST'] ? ($protocol . $server['HTTP_HOST'] . $server['REQUEST_URI']) : '';

            $data['request'] = array(
                'url'            => $url,
                'request_method' => strtolower($server['REQUEST_METHOD']),
                'remote_ip'      => $server['REMOTE_ADDR'],
                'headers'        => $headers,
                'session'        => $session
            );

            $parameters = array_merge($_GET, $_POST);

            foreach (Exceptional::getBlackList() as $filter) {
                $parameters = $this->filterParams($parameters, $filter);
            }

            if (!empty($parameters)) {
                $data['request']['parameters'] = $parameters;
            }
        } else {
            $data['request'] = array();
        }

        $data['request']['controller'] = Exceptional::getController();
        $data['request']['action']     = Exceptional::getAction();

        $this->data = $data;
    }

    /**
     * Get headers
     *
     * @return array
     */
    private function getAllHeaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = array();
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headerName = strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-');
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Create unique hash
     *
     * @return string
     */
    public function uniquenessHash()
    {
        return md5(implode('', $this->backtrace));
    }

    /**
     * Create JSON data
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Fix data set
     *
     * @param array $dataSet
     * @param array $keys
     *
     * @return array
     */
    private function fillKeys(array $dataSet, array $keys)
    {
        foreach ($keys as $key) {
            if (!isset($dataSet[$key])) {
                $dataSet[$key] = false;
            }
        }

        return $dataSet;
    }

    /**
     * Filter data set
     *
     * @param array  $parameters
     * @param string $term
     *
     * @return array
     */
    private function filterParams(array $parameters, $term)
    {
        foreach ($parameters as $key => $value) {
            if (preg_match('/' . $term. '/i', $key)) {
                $parameters[$key] = '[FILTERED]';
            } elseif (is_array($value)) {
                $parameters[$key] = $this->filterParams($value, $term);
            }
        }

        return $parameters;
    }
}
