<?php


namespace NorthStack\NorthStackClient\API\Infra;


use NorthStack\NorthStackClient\API\BaseApiClient;

class ResourceClient extends BaseApiClient
{
    protected $apiName = 'stackresources';

    public function createResource(
        string $accessToken,
        string $stackId,
        string $resourceType,
        string $label
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("infra/stacks/{$stackId}/resources", ['json' => [
                'type' => $resourceType,
                'label' => $label,
            ]]);
    }

    public function listResources(string $accessToken, string $stackId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("infra/stacks/{$stackId}/resources");
    }
}
