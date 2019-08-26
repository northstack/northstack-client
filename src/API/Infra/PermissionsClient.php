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

    public function removeUser(string $token, string $stackId, string $orgUserId)
    {
        return $this->guzzle($this->getBearerTokenMiddleware($token))
            ->delete("infra/stacks/{$stackId}/users/{$orgUserId}");
    }

    public function setPermissions(string $token, string $targetType, $targetId, string $orgUserId, int $permissions)
    {
        $data = [
            'orgUserId' => $orgUserId,
            'permissions' => $permissions,
        ];

        switch ($targetType) {
            case 'stack':
                $uri = "infra/stacks/{$targetId}/permissions";
                break;
            case 'environment':
                $uri = "infra/environments/{$targetId}/permissions";
                break;
            case 'app':
                $uri = "infra/apps/{$targetId}/permissions";
                break;
            case 'deployment':
                $uri = "infra/deployments/{$targetId}/permissions";
                break;
            default:
                throw new \RuntimeException('Unknown target type: '.$targetType);
        }
        return $this->guzzle($this->getBearerTokenMiddleware($token))
            ->put($uri, ['json' => $data]);
    }
}
