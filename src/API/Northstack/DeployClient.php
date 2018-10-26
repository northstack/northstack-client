<?php


namespace NorthStack\NorthStackClient\API\Northstack;


use NorthStack\NorthStackClient\API\BaseApiClient;

class DeployClient extends BaseApiClient
{
    protected $apiName = 'northstack-deploy';

    public function build(
        $accessToken,
        $sappId,
        $releaseTitle,
        $releaseNotes = null
    )
    {
        $data = [
            'title' => $releaseTitle,
        ];
        if ($releaseNotes) {
            $data['notes'] = $releaseNotes;
        }

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/deploy/app/$sappId/build", [
                'json' => $data,
            ]);
    }

    public function gateway($accessToken, $releaseId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/deploy/release/$releaseId/gateway");
    }

    public function run(
        $accessToken,
        $releaseId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/deploy/release/$releaseId/run");
    }

    public function test(
        $accessToken,
        $releaseId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/deploy/release/$releaseId/test");
    }

    public function stopOld(
        $accessToken,
        $releaseId
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/northstack/deploy/release/$releaseId/stop-old");
    }
}
