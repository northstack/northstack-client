<?php


namespace NorthStack\NorthStackClient\API\Orgs;


use NorthStack\NorthStackClient\API\BaseApiClient;

class OrgsClient extends BaseApiClient
{
    protected $apiName = 'orgs';

    public function signup(
        $organizationName,
        $username,
        $password,
        $firstName,
        $lastName,
        $email
    )
    {
        return $this->guzzle()->post(
            '/orgs/signup',
            [
                'json' => [
                    'name' => $organizationName,
                    'username' => $username,
                    'password' => $password,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'email' => $email,
                ],
            ]
        );
    }

    public function get($accessToken, $orgId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/orgs/$orgId");
    }

    public function listUsers($accessToken, $orgId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/orgs/$orgId/users");
    }

    public function getUser($accessToken, $userId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/orgs/user/$userId");
    }

    public function getCurrentUserPermissions($accessToken)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/orgs/permissions");
    }
}
