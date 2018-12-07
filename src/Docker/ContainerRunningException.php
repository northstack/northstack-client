<?php

namespace NorthStack\NorthStackClient\Docker;

class ContainerRunningException extends AbstractContainerException
{

    protected function setMessage($nameOrId)
    {
        return "Container {$nameOrId} is already running";
    }
}
