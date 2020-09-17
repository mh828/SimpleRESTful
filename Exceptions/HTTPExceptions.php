<?php


namespace SimpleRESTful\Exceptions;


use Throwable;

class HTTPExceptions extends \Exception
{
    private $responseCode = 200;

    public function __construct($httpResponseCode, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->responseCode = $httpResponseCode;
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return "Page Not Found";
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }
}