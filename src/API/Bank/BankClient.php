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

    public function statsForAppPostPipelineRefactor(
        $accessToken,
        $orgId,
        array $sappId = [],
        array $statType = [],
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
        $args['appIds'] = $sappId;
        $args['type'] = $statType;

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("bank/stats/{$orgId}/traffic", ['query' => $args]);
    }
}
