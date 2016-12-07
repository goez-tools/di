<?php

namespace Goez\Di;

use ReflectionClass;
use ReflectionParameter;

class Container
{
    private static $instance;

    /**
     * @var array
     */
    protected static $map = [];

    private function __construct()
    {
    }

    /**
     * @return Container
     */
    public static function createInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @return void
     */
    public static function resetInstance()
    {
        static::$instance = null;
        static::$map = [];
    }

    /**
     * @param $name
     * @param \Closure $closure
     */
    public function bind($name, $closure)
    {
        static::$map[$name] = $closure;
    }

    /**
     * @param $name
     * @param array $givenArgs
     * @return mixed
     * @internal param null $args
     */
    public function make($name, array $givenArgs = [])
    {
        if (isset(static::$map[$name])) {
            $closure = static::$map[$name];
            if ($closure instanceof \Closure) {
                return $closure($this);
            } else {
                $name = $closure;
            }
        }

        if (!class_exists($name)) {
            return null;
        }

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
    }

    /**
     * @param $givenArgs
     * @param $reflectionParams
     * @return array
     */
    private function mergeParams($givenArgs, $reflectionParams)
    {
        return array_map($this->makeFromParameter($givenArgs), $reflectionParams);
    }

    /**
     * @param $givenArgs
     * @return \Closure
     */
    private function makeFromParameter($givenArgs)
    {
        return function (ReflectionParameter $param) use (&$givenArgs) {
            $class = $param->getClass();
            return $class instanceof ReflectionClass ?
                $this->make($class->getName()) :
                $this->getParamValue($param, array_shift($givenArgs));
        };
    }

    /**
     * @param $param
     * @param $givenArg
     * @return mixed|null
     */
    private function getParamValue($param, $givenArg)
    {
        return $givenArg ?: $this->getParamDefaultValue($param);
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
}
