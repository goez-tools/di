<?php

namespace Stub;

class Command
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $message;

    /**
     * Command constructor.
     * @param string $name
     * @param string $message
     */
    public function __construct($name, $message = 'Hello')
    {
        $this->name = $name;
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}