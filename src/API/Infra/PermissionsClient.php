<?php


namespace NorthStack\NorthStackClient\API\Infra;


use NorthStack\NorthStackClient\API\BaseApiClient;

class PermissionsClient extends BaseApiClient
{
    protected $apiName = 'stackperms';

    public function getMyPermissions(string $token)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($token))
            ->get('infra/permissions/me');
    }

    public function permissionTypes()
    {
        return $this->guzzle()
            ->get('infra/permissions');
    }

    public function checkAccess(
        string $token,
        string $targetType,
        string $targetId,
        int $permissions,
        int $orgLevelPermission,
        string $orgUserId = null
    )
    {
        $query = [
            'targetType' => $targetType,
            'targetId' => $targetId,
            'permissions' => $permissions,
            'orgLevelPermission' => $orgLevelPermission,
        ];
        if ($orgUserId) {
            $query['orgUserId'] = $orgUserId;
        }

        return $this->guzzle($this->getBearerTokenMiddleware($token))
            ->get('infra/permissions/check', ['query' => $query]);
    }
}
