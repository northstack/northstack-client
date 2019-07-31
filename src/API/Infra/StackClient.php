<?php


namespace NorthStack\NorthStackClient\API\Infra;


use NorthStack\NorthStackClient\API\BaseApiClient;

class StackClient extends BaseApiClient
{
    protected $apiName = 'stacks';

    public function createStack(
        string $accessToken,
        string $orgId,
        string $stackType,
        string $label
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post('infra/stacks', ['json' => [
                'orgId' => $orgId,
                'type' => $stackType,
                'label' => $label,
            ]]);
    }

    public function updateStack(string $accessToken, string $stackId, array $data)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->patch('infra/stacks/'.$stackId, ['json' => $data]);
    }

    public function listStacks(string $accessToken, string $orgId, $label = null)
    {
        $query = ['orgId' => $orgId];
        if ($label) {
            $query['label'] = $label;
        }

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get('infra/stacks', ['query' => $query]);
    }

    public function getStack(string $accessToken, string $stackId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("infra/stacks/{$stackId}");
    }
}
