<?php


use Interfaces\IPipeline;

class PipeLineCore
{
    protected $__autoloader_dirs = [__DIR__ . '/classes'];
    protected $__pipelines = [];
    protected $__injections = [];

    public function __construct()
    {
        spl_autoload_register(function ($classPath) {
            foreach ($this->__autoloader_dirs as $dir) {
                $file = preg_replace("/[\\\\\/]+/", "/", $dir . '//\\//' . $classPath) . '.php';
                if (file_exists($file)) {
                    include_once $file . '';
                    break;
                }
            }
        });
    }

    public function AddAutoloadDir($dir)
    {
        array_push($this->__autoloader_dirs, $dir);
    }

    public function AddInjectionClass($class)
    {
        array_push($this->__injections, $class);
    }

    public function GetInjectionsInstance(ReflectionClass $type)
    {
        if ($type->isInstance($this))
            return $this;
        foreach ($this->__injections as $injection) {
            if ($type->isInstance($injection))
                return $injection;
        }
        return $this->InstantiateClassReflection($type);
    }

    public function GetInjectionsClassInstance(string $name)
    {
        try {
            return $this->GetInjectionsInstance(new ReflectionClass($name));
        } catch (ReflectionException $e) {
            return null;
        }
    }

    public function AddPipeLine(string $pipeline)
    {
        array_push($this->__pipelines, $pipeline);
    }

    public function RunPipelines()
    {
        try {
            if ($pipe = array_shift($this->__pipelines)) {
                $rc = new ReflectionClass($pipe);
                if ($rc->implementsInterface(IPipeline::class)) {
                    $constructor = $rc->getConstructor();
                    $args = $constructor ? $this->CallableArgumentGenerator(...$constructor->getParameters()) : [];
                    $instance = $rc->newInstance(...$args);

                    $rc->getMethod('handle')->getClosure($instance)($this, Closure::fromCallable([$this, 'RunPipelines']));
                }
            }
        } catch (Exception $exception) {

        }
    }

    public function CallableArgumentGenerator(ReflectionParameter ...$parameters): array
    {
        $args = [];
        foreach ($parameters as $parameter) {
            if ($cls = $parameter->getClass()) {
                array_push($args, $this->GetInjectionsInstance($cls));
            } else {
                array_push($args, $this->GetParameterValue($parameter));
            }
        }
        return $args;
    }

    public function InstantiateClass(string $name)
    {
        try {
            if ($class = new ReflectionClass($name)) {
                return $this->InstantiateClassReflection($class);
            }

        } catch (Exception $exception) {

        }
    }

    protected function InstantiateClassReflection(ReflectionClass $class)
    {
        if ($constructor = $class->getConstructor()) {
            return $class->newInstance(...$this->CallableArgumentGenerator(...$constructor->getParameters()));
        } else {
            return $class->newInstance();
        }
    }

    public function GetParameterValue(ReflectionParameter $parameter){
        try {
            if($parameter->isDefaultValueAvailable())
                return $parameter->getDefaultValue();
            else if($parameter->isDefaultValueConstant())
                return $parameter->getDefaultValueConstantName();
        } catch (Exception $exception) {

        }
        return '';
    }
}