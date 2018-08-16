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
        $cluster,
        $primaryDomain
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post('/sapps', [
                'json' => [
                    'name' => $name,
                    'orgId' => $orgId,
                    'cluster' => $cluster,
                    'primaryDomain' => $primaryDomain,
                ],
            ]);
    }

    public function requestDeploy(
        $accessToken,
        $sappId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/sapps/{$sappId}/deploy");
    }

    public function deploy(
        $accessToken,
        $sappId,
        $config,
        $buildConfig,
        $domains
    ) {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/sapps/{$sappId}/deploy", [
                'json' => [
                    'config' => $config,
                    'build' => $buildConfig,
                    'domains' => $domains,
                ],
            ]);
    }
}
