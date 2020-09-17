<?php

namespace SimpleRESTful;

use SimpleRESTful\Exceptions\HTTPExceptions;
use SimpleRESTful\HTTP\Request;

class Core
{
    private $LoaderDirectories = [];
    /** @var \Closure[] */
    private $middleWars = [];
    /** @var Request */
    private $request = null;

    public function __construct()
    {
        spl_autoload_register(array($this, 'autoloader'));
    }

    public function processRequest()
    {
        $this->request = Request::_getInstance();

        try {
            return $this->instantiateRequest($this->request->getClass(), $this->request->getMethod());

        } catch (HTTPExceptions $exception) {
            http_response_code($exception->getResponseCode());
        }

        return null;
    }

    public function addAutoloaderDirectory($dir)
    {
        $this->LoaderDirectories[] = $dir;
    }

    public function addMiddleware(callable $callable)
    {
        $this->middleWars[] = $callable;
    }

    public function startMiddlewares()
    {
        $this->runNextMiddleware();;
    }

    private function runNextMiddleware()
    {
        if (count($this->middleWars) > 0)
            array_shift($this->middleWars)(function () {
                $this->runNextMiddleware();
            });
    }

    private function autoloader($class)
    {
        $path = str_replace('\\', '/', $class);
        foreach ($this->LoaderDirectories as $dir) {
            if (@include $dir . '/' . $path . '.php')
                break;
        }
    }

    private function instantiateRequest($class, $method)
    {
        if (!$class) {
            throw new HTTPExceptions(404);
        }

        $class_reflection = new \ReflectionClass($class);
        $class_instance = $this->instantiateClass($class_reflection);
        if ($method && $class_reflection->hasMethod($method)) {
            $method_reflection = $class_reflection->getMethod($method);
            return $method_reflection->invoke($class_instance, ...$this->methodParamsGenerator($method_reflection));
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
}