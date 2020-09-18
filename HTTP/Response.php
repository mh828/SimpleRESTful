<?php


namespace SimpleRESTful\HTTP;


class Response implements \SimpleRESTful\SingletonInterface
{
    //<editor-fold desc="Static Singleton implement">
    private static $_instance = null;

    public static function _getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new Response();
        }

        return self::$_instance;
    }

    //</editor-fold>

    private $_contentType = 'application/json; charset=UTF-8';
    private $_responseCode = 200;

    private $_responseBody;


    //<editor-fold desc="Setters And Getters">

    /**
     * @param int $responseCode
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->_responseCode = $responseCode;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->_contentType;
    }

    /**
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->_responseCode;
    }

    /**
     * @param mixed $_responseBody
     */
    public function setResponseBody($_responseBody): void
    {
        $this->_responseBody = $_responseBody;
    }

    /**
     * @return mixed
     */
    public function getResponseBody()
    {
        return $this->_responseBody;
    }

    //</editor-fold>

    public function generateOutput()
    {
        http_response_code($this->_responseCode);
        header('Content-Type: ' . $this->_contentType);
        echo json_encode($this->_responseBody);
    }
}