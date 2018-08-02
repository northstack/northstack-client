<?php

namespace Pagely\NorthstackClient\API;

use Pagely\NorthstackClient\Client\GuzzleTrait;
use Pagely\NorthstackClient\RequestChain;
use Psr\Log\LoggerInterface;

class BaseApiClient
{
    use GuzzleTrait;

    public function __construct(RequestChain $requestChain, $baseUrl = 'https://mgmt.pagely.com/api/', LoggerInterface $logger)
    {
        $this->requestChain = $requestChain;
        $this->baseUrl = $baseUrl;
        $this->logger = $logger;
    }
}
