<?php


namespace NorthStack\NorthStackClient\API\Orgs;


use NorthStack\NorthStackClient\API\BaseApiClient;

class OrgsClient extends BaseApiClient
{
    protected $apiName = 'orgs';

    public function signup(
        $token,
        $organizationName,
        $username,
        $password,
        $name,
        $email
    )
    {
        return $this->guzzle($this->getBearerTokenMiddleware($token))->post(
            '/orgs/signup',
            [
                'json' => [
                    'name' => $organizationName,
                    'username' => $username,
                    'password' => $password,
                    'personName' => $name,
                    'email' => $email
                ],
            ]
        );
    }
}
