<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\API\Exception\ApiException;
use Docker\API\Model\ErrorResponse;

class DockerActionException extends \Exception
{
    public static function fromException(\Exception $e)
    {
        // All docker-php exceptions use the ApiException interface, but
        // getErrorResponse() is not part of the interface, so we have to
        // check by hand :(
        if ($e instanceof ApiException
            && method_exists($e, 'getErrorResponse')
            && $e->getErrorResponse() instanceof ErrorResponse)
        {
            $msg = $e->getErrorResponse()->getMessage();
            return new self($msg);
        }
        return $e;
    }
}
