<?php
namespace NorthStack\NorthStackClient\API\Bank;

use NorthStack\NorthStackClient\API\BaseApiClient;

class BankClient extends BaseApiClient
{
    protected $apiName = 'bank';

    public function statsForApp(
        $accessToken,
        $sappId,
        $statType,
        $to = null,
        $from = null
    )
    {
        $args = [];
        if (!empty($to)) {
            $args['toDate'] = $to;
        }
        if (!empty($from)) {
            $args['fromDate'] = $from;
        }
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("bank/stats/$statType/sapp/$sappId", $args);
    }

    public function statsForOrg(
        $accessToken,
        $orgId,
        $statType,
        array $filters
    )
    {
        $args = $filters;
        $args['appIds'] = $sappId;

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("bank/stats/{$orgId}/{$statType}", ['query' => $args]);
    }
}
