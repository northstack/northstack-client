<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\API\Model\ErrorResponse;

class DockerActionException extends \Exception
{
    public static function fromException(\Exception $e)
    {
        if (method_exists($e, 'getErrorResponse')
            && $e->getErrorResponse() instanceof ErrorResponse)
        {
            $msg = $e->getErrorResponse()->getMessage();
            return new self($msg);
        }
        return $e;
    }
}
