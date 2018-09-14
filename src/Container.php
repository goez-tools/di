<?php

namespace Goez\Di;

use ReflectionClass;
use ReflectionParameter;

class Container
{
    private static $containerInstance;

    /**
     * @var array
     */
    protected static $map = [];

    /**
     * @var array
     */
    protected static $singletonInstances = [];

    private function __construct()
    {
    }

    /**
     * @return Container
     */
    public static function getInstance()
    {
        if (static::$containerInstance === null) {
            static::$containerInstance = new static();
        }
        return static::$containerInstance;
    }

    /**
     * @return void
     */
    public static function resetInstance()
    {
        static::$containerInstance = null;
        static::$map = [];
        static::$singletonInstances = [];
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return isset(static::$map[$name]) || isset(static::$singletonInstances[$name]);
    }

    /**
     * @param $name
     * @param \Closure|string $closure
     */
    public function bind($name, $closure)
    {
        if ($this->isClassName($closure) || $this->isClosure($closure)) {
            static::$map[$name] = $closure;
        }
    }

    /**
     * @param $name
     * @param $instance
     */
    public function instance($name, $instance)
    {
        if (is_object($instance)) {
            static::$map[$name] = $instance;
        }
    }

    /**
     * @param $name
     * @param $instanceOrClosure
     */
    public function singleton($name, $instanceOrClosure)
    {
        if (!isset(static::$singletonInstances[$name])) {
            $newName = $name . 'Singleton';
            if ($this->isClosure($instanceOrClosure) || $this->isInstance($name, $instanceOrClosure)) {
                static::$map[$newName] = $instanceOrClosure;
            }
            static::$singletonInstances[$name] = $this->make($newName);
            unset(static::$map[$newName]);
        }
    }

    /**
     * @param $name
     * @param array $givenArgs
     * @return mixed
     * @internal param null $args
     */
    public function make($name, array $givenArgs = [])
    {
        $resolver = $this->getResolver([
            $this->resolveFromSingleton(),
            $this->resolveFromMap(),
            $this->resolveClassName(),
            $this->resolveNestedInjection(),
        ]);
        return $resolver($name, $givenArgs);
    }

    /**
     * @param array $resolvers
     * @return mixed|null
     */
    private function getResolver(array $resolvers)
    {
        $defaultResolver = array_pop($resolvers);
        $resolver = array_reduce(array_reverse($resolvers), function ($next, $resolver) {
            return $resolver($next);
        }, $defaultResolver());
        return $resolver;
    }

    /**
     * @return \Closure
     */
    private function resolveFromSingleton()
    {
        return function (\Closure $next) {
            return function ($name, &$givenArgs) use ($next) {
                if (isset(static::$singletonInstances[$name])) {
                    return static::$singletonInstances[$name];
                }
                return $next($name, $givenArgs);
            };
        };
    }

    /**
     * @return \Closure
     */
    private function resolveFromMap()
    {
        return function (\Closure $next) {
            return function ($name, &$givenArgs) use ($next) {
                if (isset(static::$map[$name])) {
                    $closure = static::$map[$name];
                    if ($this->isClosure($closure)) {
                        return $closure($this);
                    }

                    if ($this->isInstance($name, $closure)) {
                        return $closure;
                    }

                    if ($this->isSingleton($name)) {
                        return $closure;
                    }

                    if (is_object($closure)) {
                        return $closure;
                    }

                    $name = $closure;
                }
                return $next($name, $givenArgs);
            };
        };
    }

    /**
     * @return \Closure
     */
    private function resolveClassName()
    {
        return function (\Closure $next) {
            return function ($name, &$givenArgs) use ($next) {
                if (!$this->isClassName($name)) {
                    return null;
                }
                return $next($name, $givenArgs);
            };
        };
    }

    /**
     * @return \Closure
     */
    private function resolveNestedInjection()
    {
        return function () {
            return function ($name, &$givenArgs) {
                $reflectionClass = new ReflectionClass($name);
                $reflectionConstructor = $reflectionClass->getConstructor();
                if (null === $reflectionConstructor) {
                    return $reflectionClass->newInstance();
                }
                $reflectionParams = $reflectionConstructor->getParameters();
                $args = $this->mergeParams($givenArgs, $reflectionParams);
                return !empty($args) ?
                    $reflectionClass->newInstanceArgs($args) :
                    new $name();
            };
        };
    }

    /**
     * @param $givenArgs
     * @param $reflectionParams
     * @return array
     */
    private function mergeParams(&$givenArgs, $reflectionParams)
    {
        return array_map($this->makeFromParameter($givenArgs), $reflectionParams);
    }

    /**
     * @param $givenArgs
     * @return \Closure
     */
    private function makeFromParameter(&$givenArgs)
    {
        return function (ReflectionParameter $param) use (&$givenArgs) {
            $class = $param->getClass();
            return $class instanceof ReflectionClass ?
                $this->getParamValueOrMakeObject($class, $givenArgs) :
                $this->getParamValue($param, array_shift($givenArgs));
        };
    }

    /**
     * @param ReflectionClass $class
     * @param array $givenArgs
     * @return array
     */
    private function getParamValueOrMakeObject(ReflectionClass $class, &$givenArgs)
    {
        $name = $class->getName();
        return isset($givenArgs[0]) && $givenArgs[0] instanceof $name ?
            array_shift($givenArgs) :
            $this->make($name);
    }

    /**
     * @param $param
     * @param $givenArg
     * @return mixed|null
     */
    private function getParamValue($param, $givenArg)
    {
        return (null !== $givenArg) ? $givenArg : $this->getParamDefaultValue($param);
    }

    /**
     * @param ReflectionParameter $param
     * @return mixed|null
     */
    private function getParamDefaultValue(ReflectionParameter $param)
    {
        return $param->isDefaultValueAvailable() ?
            $param->getDefaultValue() :
            null;
    }

    /**
     * @param $instanceOrClosure
     * @return bool
     */
    private function isClassName($instanceOrClosure)
    {
        return is_string($instanceOrClosure) &&
            class_exists($instanceOrClosure);
    }

    /**
     * @param $name
     * @return bool
     */
    private function isSingleton($name)
    {
        return is_string($name) && preg_match('#Singleton$#', $name);
    }

    /**
     * @param $instanceOrClosure
     * @return bool
     */
    private function isClosure($instanceOrClosure)
    {
        return ($instanceOrClosure instanceof \Closure);
    }

    /**
     * @param $name
     * @param $instanceOrClosure
     * @return bool
     */
    private function isInstance($name, $instanceOrClosure)
    {
        return is_object($instanceOrClosure) && ($instanceOrClosure instanceof $name);
    }
}
