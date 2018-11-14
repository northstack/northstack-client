<?php


namespace NorthStack\NorthStackClient\API\Sapp;


use NorthStack\NorthStackClient\API\BaseApiClient;

class SecretsClient extends BaseApiClient
{
    protected $apiName = 'sapp-secrets';

    public function listSecrets(string $accessToken, string $sappId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/sapps/{$sappId}/secrets");
    }

    public function setSecret(string $accessToken, string $sappId, string $key, $value)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->put("/sapps/{$sappId}/secrets/{$key}", [
                'json' => [
                    'secret' => $value,
                ],
            ]);
    }

    public function removeSecret(string $accessToken, string $sappId, string $key)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->delete("/sapps/{$sappId}/secrets/{$key}");
    }
}
