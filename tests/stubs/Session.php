<?php

namespace Stub;

class Session
{
    /**
     * @var string
     */
    private $namespace;

    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
}