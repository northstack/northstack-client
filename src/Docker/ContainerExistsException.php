<?php

namespace NorthStack\NorthStackClient\Docker;

class ContainerExistsException extends AbstractContainerException
{
    protected function setMessage($nameOrId)
    {
        return "Container {$nameOrId} already exists";
    }

}
