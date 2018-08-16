<?php

namespace NorthStack\NorthStackClient\API;

use NorthStack\NorthStackClient\Client\GuzzleTrait;
use NorthStack\NorthStackClient\RequestChain;
use Psr\Log\LoggerInterface;

class BaseApiClient
{
    use GuzzleTrait;

    public function __construct(RequestChain $requestChain, $baseUrl = null, LoggerInterface $logger = null)
    {
        $this->requestChain = $requestChain;
        $this->baseUrl = $baseUrl ?:
            getenv('MGMT_API_URL')
        ;
        $this->logger = $logger;
    }
}
