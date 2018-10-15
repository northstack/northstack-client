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
        $email,
        $phone,
        $phoneCountry = '1'
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
                    'phone' => $phone,
                    'phoneCountry' => $phoneCountry,
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
            ->get('/orgs/permissions');
    }

    public function verify($accessToken, $orgId, $code)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/orgs/{$orgId}/verify", [
                'json' => [
                    'code' => $code,
                ],
            ]);
    }

    public function verifyRequest($accessToken, $orgId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/orgs/{$orgId}/verify-request");
    }

    public function listOrgs($accessToken)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/orgs");
    }
}
