<?php


namespace NorthStack\NorthStackClient\API\Infra;


use NorthStack\NorthStackClient\API\BaseApiClient;

class DeploymentClient extends BaseApiClient
{
    protected $apiName = 'stackdeployments';

    public function createDeployment(
        string $accessToken,
        string $envId,
        string $appId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("infra/stackenvs/{$envId}/deployments", ['json' => [
                'appId' => $appId,
            ]]);
    }

    public function listDeployments(
        string $accessToken,
        string $envId,
        string $appId = null
    )
    {
        $query = [];
        if ($appId) {
            $query['appId'] = $appId;
        }

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("infra/stackenvs/{$envId}/deployments", ['query' => $query]);
    }
}
