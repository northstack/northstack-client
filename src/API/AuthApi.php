<?php
namespace NorthStack\NorthStackClient\API;

class AuthApi extends BaseApiClient
{
    protected $apiName = 'auth';

    public function login($username, $password, $mfa = null, array $scope = [], $clientId = 'org')
    {
        $data = [
            'form_params' => [
                'client_id' => $clientId,
                'username' => $username,
                'password' => $password,
                'grant_type' => 'password',
            ],
        ];
        if (!empty($scope)) {
            $data['form_params']['scope'] = implode(' ', $scope);
        }

        if ($mfa) {
            $data['form_params']['mfa'] = $mfa;
        }

        return $this->guzzle()->post('auth/access_token', $data);
    }

    public function clientLogin($clientId, $secret, array $scope = [])
    {
        $data = [
            'form_params' => [
                'client_id' => $clientId,
                'client_secret' => $secret,
                'grant_type' => 'client_credentials',
            ],
        ];

        if (!empty($scope)) {
            $data['form_params']['scope'] = implode(' ', $scope);
        }
        return $this->guzzle()->post('auth/access_token', $data);
    }

    public function validate($token)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($token))->get("auth/validate/{$token}");
    }
}
