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
                    'organization' => $organizationName,
                    'username' => $username,
                    'password' => $password,
                    'name' => $name,
                    'email' => $email
                ],
            ]
        );
    }
}
