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
        $appType,
        $frameworkVersion = null,
        $frameworkConfig = null
    )
    {
        $data = [
            'appName' => $name,
            'cluster' => $cluster,
            'primaryDomain' => $primaryDomain,
            'appType' => $appType,
        ];
        if ($orgId) {
            $data['orgId'] = $orgId;
        }

        if ($frameworkVersion) {
            $data['frameworkVersion'] = $frameworkVersion;
        }

        if ($frameworkConfig) {
            $data['frameworkConfig'] = json_encode($frameworkConfig);
        }

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post('sapps/apps', [
                'json' => $data,
            ]);
    }

    public function requestDeploy(
        $accessToken,
        $sappId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("sapps/{$sappId}/deploy");
    }

    public function get(
        $accessToken,
        $sappId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("sapps/{$sappId}");
    }

    public function getAppBySappId(
        $accessToken,
        $sappId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("sapps/app/{$sappId}");
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
            ->post("sapps/{$sappId}/deploy", [
                'json' => [
                    'config' => $config,
                    'build' => $buildConfig,
                    'domains' => $domains,
                ],
            ]);
    }

    public function search(
        $accessToken,
        $name = null,
        $orgId = null,
        $cluster = null
    ) {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("sapps", [
                'query' => [
                    'name' => $name,
                    'orgId' => $orgId,
                    'cluster' => $cluster,
                ],
            ]);
    }
}
