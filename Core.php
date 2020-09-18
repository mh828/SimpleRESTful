<?php

namespace SimpleRESTful;

use SimpleRESTful\Exceptions\HTTPExceptions;
use SimpleRESTful\HTTP\Request;
use SimpleRESTful\HTTP\Response;

class Core
{
    private $LoaderDirectories = [];
    /** @var \Closure[] */
    private $middleWars = [];
    /** @var Request */
    private $request = null;

    private $_defaultClass = null;
    private $_defaultMethodName = null;

    public function __construct()
    {
        spl_autoload_register(array($this, 'autoloader'));
    }

    public function processRequest()
    {
        $this->request = Request::_getInstance();

        try {
            Response::_getInstance()->setResponseBody(
                $this->instantiateRequest($this->request->getClass() ?? $this->_defaultClass, $this->request->getMethod() ?? $this->_defaultMethodName)
            );

        } catch (HTTPExceptions $exception) {
            Response::_getInstance()->setResponseCode($exception->getResponseCode());
        }

        return null;
    }

    public function addAutoloaderDirectory($dir)
    {
        $this->LoaderDirectories[] = $dir;
    }

    public function addMiddleware(\Closure $callable)
    {
        $this->middleWars[] = $callable;
    }

    public function run()
    {
        $this->addMiddleware(function ($next) {
            $this->processRequest();
            $next();
        });
        $this->runNextMiddleware();
    }

    private function runNextMiddleware()
    {
        if (count($this->middleWars) > 0) {
            /** @var \Closure $callable */
            $callable = array_shift($this->middleWars);/*();*/
            $next = function () {
                $this->runNextMiddleware();
            };

            $reflection_method = new \ReflectionFunction($callable);
            $params = $this->functionParamsGenerator($reflection_method);
            $params[0] = $next;
            $reflection_method->invoke(...$params);
        }
    }

    private function autoloader($class)
    {
        $path = str_replace('\\', '/', $class);
        foreach ($this->LoaderDirectories as $dir) {
            if (@include $dir . '/' . $path . '.php')
                break;
        }
    }

    /**
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     * @return mixed|null
     * @throws HTTPExceptions
     * @throws \ReflectionException
     */
    private function instantiateRequest($class, $method)
    {
        if (!$class) {
            throw new HTTPExceptions(404);
        }

        $class_instance = $this->instantiateClass($class);
        if ($method && $class->hasMethod($method)) {
            $method_reflection = $class->getMethod($method);
            return $method_reflection->invoke($class_instance, ...$this->methodParamsGenerator($method_reflection));
        } else {
            throw new HTTPExceptions(404);
        }

        return null;
    }

    private function instantiateClass(\ReflectionClass $class)
    {
        if ($constructor = $class->getConstructor()) {
            return $class->newInstance(...$this->methodParamsGenerator($constructor));
        } else {
            return $class->newInstance();
        }
    }

    private function methodParamsGenerator(\ReflectionMethod $method)
    {
        return $this->parametersHandler($method);
    }

    private function functionParamsGenerator(\ReflectionFunction $method)
    {
        return $this->parametersHandler($method);
    }

    /**
     * @param \ReflectionMethod|\ReflectionFunction $method
     * @return array
     */
    private function parametersHandler($method)
    {
        $params = [];
        foreach ($method->getParameters() as $param) {
            if ($param_class = $param->getClass()) {
                if ($param_class->implementsInterface(SingletonInterface::class))
                    $params[] = $param_class->getMethod('_getInstance')->invoke(null);
                else
                    $params[] = $this->instantiateClass($param_class);
            } else if ($param->hasType()) {
                $param_type = $param->getType();
                $params[] = $this->builtinTypesDefaultValue($param_type->__toString());
            } else {
                $params [] = null;
            }
        }

        return $params;
    }

    private function builtinTypesDefaultValue($type)
    {
        switch (strtolower($type)) {
            case 'string':
                return '';

            case 'int':
            case 'float':
                return 0;

            case 'array':
            case 'iterable':
                return [];

            case 'object':
                return new \stdClass();

            default:
                return null;
        }
    }


    //<editor-fold desc="Setters And Getters">

    /**
     * define default class to instantiate when class not found
     * @param string $defaultClass
     * @throws \ReflectionException
     */
    public function setDefaultClass(string $defaultClass): void
    {
        if (class_exists($defaultClass))
            $this->_defaultClass = new \ReflectionClass($defaultClass);
    }

    /**
     * define default method name to call when method name isn't defined in request (URL)
     * @param string $defaultMethodName
     */
    public function setDefaultMethodName(string $defaultMethodName): void
    {
        $this->_defaultMethodName = $defaultMethodName;
    }
    //</editor-fold>
}