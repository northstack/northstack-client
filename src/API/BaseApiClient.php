<?php

namespace NorthStack\NorthStackClient\API;

use NorthStack\NorthStackClient\Client\GuzzleTrait;
use NorthStack\NorthStackClient\Client\LoginRequiredException;
use NorthStack\NorthStackClient\RequestChain;
use Psr\Log\LoggerInterface;

class BaseApiClient
{
    use GuzzleTrait;

    protected $apiName;

    public function __construct(RequestChain $requestChain, $baseUrl = null, LoggerInterface $logger = null)
    {
        $this->requestChain = $requestChain;
        $this->baseUrl = $baseUrl ?:
            getenv('MGMT_API_URL')
        ;
        $this->logger = $logger;
        $this->setGuzzleResponseHandlers();
    }

    protected function setGuzzleResponseHandlers()
    {
        $this->setResponseHandler(401,
            function ($response) {
                throw new LoginRequiredException();
            }
        );
    }
}
