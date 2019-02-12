<?php


namespace NorthStack\NorthStackClient\API\Northstack;


use NorthStack\NorthStackClient\API\BaseApiClient;

class NorthstackClient extends BaseApiClient
{
    protected $apiName = 'northstack';

    public function launchWorker(
        $accessToken,
        $sappId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/worker/launch/$sappId");
    }

    public function stopWorkers(string $accessToken, string $sappId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/worker/stop/$sappId");
    }
}
