<?php
namespace NorthStack\NorthStackClient\API\Bank;

use NorthStack\NorthStackClient\API\BaseApiClient;

class BankClient extends BaseApiClient
{
    protected $apiName = 'bank';

    public function statsForOrg(
        $accessToken,
        $orgId,
        $statType,
        array $filters
    )
    {
        $args = $filters;

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("bank/stats/{$orgId}/{$statType}", ['query' => $args]);
    }

    public function dimensionsForOrg(
        $accessToken,
        $orgId,
        $statType,
        array $filters
    )
    {
        $args = $filters;

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("bank/stats/{$orgId}/{$statType}/dimensions", ['query' => $args]);
    }

}
