<?php


namespace NorthStack\NorthStackClient\API\Sapp;


use NorthStack\NorthStackClient\API\BaseApiClient;

class SappClient extends BaseApiClient
{
    protected $apiName = 'sapps';

    public function createApp(
        $accessToken,
        $name,
        $orgId,
        $environment,
        $cluster,
        $primaryDomain,
        $domains,
        $config
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post('/sapps', [
                'json' => [
                    'name' => $name,
                    'orgId' => $orgId,
                    'environment' => $environment,
                    'cluster' => $cluster,
                    'primaryDomain' => $primaryDomain,
                    'domains' => $domains,
                    'config' => $config,
                ],
            ]);
    }
}
