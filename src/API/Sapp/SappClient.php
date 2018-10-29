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
        $primaryDomain,
        $appType
    )
    {
        $data = [
            'name' => $name,
            'cluster' => $cluster,
            'primaryDomain' => $primaryDomain,
            'appType' => $appType,
        ];
        if ($orgId) {
            $data['orgId'] = $orgId;
        }

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post('/sapps', [
                'json' => $data,
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

    public function get(
        $accessToken,
        $sappId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/sapps/{$sappId}");
    }

    public function update(string $accessToken, string $sappId, $data)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->patch("sapps/{$sappId}",['json' => $data]);
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
