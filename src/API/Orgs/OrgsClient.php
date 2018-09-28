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

    public function get($orgId)
    {
        return $this->guzzle()->get("/orgs/$orgId");
    }

    public function listUsers($orgId)
    {
        return $this->guzzle()->get("/orgs/$orgId/users");
    }

    public function getUser($userId)
    {
        return $this->guzzle()->get("/orgs/user/$userId");
    }
}
