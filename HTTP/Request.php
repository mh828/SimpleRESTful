<?php

namespace SimpleRESTful\HTTP;

use SimpleRESTful\SingletonInterface;

class Request implements SingletonInterface
{
    private static $instance = null;

    public static function _getInstance()
    {
        if (!self::$instance)
            self::$instance = new Request();

        return self::$instance;
    }

    private $_class = null;
    private $_method = null;
    private $_parameters = null;

    public function __construct($request = null)
    {
        if (is_null($request))
            $request = $this->requestURI();

        $this->parseRequest($this->clearVulnerability(rawurldecode($request)) );
    }

    /**
     * find requested uri
     * @param null $request_url default equals  $_SERVER['REQUEST_URI']
     * @param null $entry_point default equals dirname($_SERVER['SCRIPT_NAME'])
     * @return string
     */
    public function requestURI($request_url = null, $entry_point = null)
    {
        if (is_null($request_url))
            $request_url = $_SERVER['REQUEST_URI'];
        if (is_null($entry_point))
            $entry_point = dirname($_SERVER['SCRIPT_NAME']);

        return str_replace($entry_point, '', $request_url);
    }

    private function parseRequest($request)
    {
        $params = array_filter(explode('/', $request));
        $entry_pint = '';

        while (!empty($params)) {
            $entry_pint .= '\\' . array_shift($params);
            if (class_exists($entry_pint)) {
                $this->_class = $entry_pint;
                $this->_method = array_shift($params);
                break;
            }
        }

        $this->_parameters = $params;
    }

    public function getClass()
    {
        return $this->_class;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getParameters()
    {
        return $this->_parameters;
    }

    public function clearVulnerability($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}