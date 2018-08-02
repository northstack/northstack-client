<?php

namespace Pagely\NorthstackClient;

class RequestChain
{
    protected $requestId;
    protected $requestChain;

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param mixed $requestId
     * @return RequestChain
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestChain()
    {
        return $this->requestChain;
    }

    /**
     * @param mixed $requestChain
     * @return RequestChain
     */
    public function setRequestChain($requestChain)
    {
        $this->requestChain = $requestChain;
        return $this;
    }
}
