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

    public function addUser($accessToken, $orgId, $firstName, $lastName, array $permissions, $email = null)
    {
        $data = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'permissions' => $permissions,
        ];

        if ($email) {
            $data['email'] = $email;
        }

        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/orgs/$orgId/users", [
                'json' => $data,
            ]);
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

    // 2fa
    public function requestTwoFactor(string $accessToken, string $orgUserId, string $phone)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->get("/orgs/user/{$orgUserId}/2fa", [
                'query' => ['phone' => $phone],
            ]);
    }

    public function enableTwoFactor(string $accessToken, string $orgUserId, string $code)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($accessToken))
            ->post("/orgs/user/{$orgUserId}/2fa", [
                'json' => ['code' => $code],
            ]);
    }
}
