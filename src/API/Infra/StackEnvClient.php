<?php


namespace NorthStack\NorthStackClient\API\Infra;


use NorthStack\NorthStackClient\API\BaseApiClient;

class StackEnvClient extends BaseApiClient
{
    protected $apiName = 'stackenv';

    public function createEnvironment(
        string $accessToken,
        string $stackId,
        string $region,
        string $label,
        array $resources
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("infra/stacks/{$stackId}/environments", ['json' => [
                'label' => $label,
                'region' => $region,
                'resources' => $resources,
            ]]);
    }

    public function listEnvironments(
        string $accessToken,
        string $stackId,
        string $label = null
    )
    {
        $data = [];
        if ($label) {
            $data = ['query' => [
                'label' => $label,
            ]];
        }
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("infra/stacks/{$stackId}/environments", $data);
    }
}
