<?php


namespace NorthStack\NorthStackClient\API\Infra;


use NorthStack\NorthStackClient\API\BaseApiClient;

class AppClient extends BaseApiClient
{
    protected $apiName = 'stackapps';

    public function createApp(
        string $accessToken,
        string $stackId,
        string $appType,
        string $label
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("infra/stacks/{$stackId}/apps", ['json' => [
                'type' => $appType,
                'label' => $label,
            ]]);
    }

    public function listApps(string $accessToken, string $stackId, string $label = null)
    {
        $query = [];
        if ($label) {
            $query['label'] = $label;
        }
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("infra/stacks/{$stackId}/apps", ['query' => $query]);
    }
}
