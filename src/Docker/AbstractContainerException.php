<?php

namespace NorthStack\NorthStackClient\Docker;

abstract class AbstractContainerException extends \Exception
{
    private $container;

    public function __construct($nameOrId)
    {
        $this->container = $nameOrId;
        $this->message = $this->setMessage($nameOrId);
        parent::__construct($this->message);
    }

    abstract protected function setMessage($nameOrId);

    public function getContainer()
    {
        return $this->container;
    }
}
