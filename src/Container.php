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
        $resolver = $this->getResolver([
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
    private function resolveFromMap()
    {
        return function (\Closure $next) {
            return function ($name, &$givenArgs) use ($next) {
                if (isset(static::$map[$name])) {
                    $closure = static::$map[$name];
                    if ($closure instanceof \Closure) {
                        return $closure($this);
                    } else {
                        $name = $closure;
                    }
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
                if (!class_exists($name)) {
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
