<?php


namespace NorthStack\NorthStackClient\Command;


use NorthStack\NorthStackClient\API\Infra\AppClient;
use NorthStack\NorthStackClient\API\Infra\DeploymentClient;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Infra\StackEnvClient;

trait StackCommandTrait
{
    /**
     * @var StackClient
     */
    protected $stackClient;
    /**
     * @var StackEnvClient
     */
    protected $envClient;
    /**
     * @var AppClient
     */
    protected $appClient;
    /**
     * @var DeploymentClient
     */
    protected $deploymentClient;

    public function getStackIdForLabel(string $accessToken, $label, $orgId)
    {
        $result = $this->stackClient->listStacks(
            $accessToken,
            $orgId,
            $label
        );

        $data = json_decode($result->getBody()->getContents());
        if ($data->{"@count"} === 1) {
            return $data->data[0]->id;
        }

        throw new \RuntimeException('Invalid label: '.$label);
    }

    public function getEnvIdForLabel(string $accessToken, $label, $stackId)
    {
        $result = $this->envClient->listEnvironments(
            $accessToken,
            $stackId,
            $label
        );

        $data = json_decode($result->getBody()->getContents());

        if ($data->{"@count"} === 1) {
            return $data->data[0]->id;
        }

        throw new \RuntimeException('Invalid label: '.$label);
    }

    public function getAppIdForLabel(string $accessToken, $label, $stackId)
    {
        $result = $this->appClient->listApps(
            $accessToken,
            $stackId,
            $label
        );

        $data = json_decode($result->getBody()->getContents());

        if ($data->{"@count"} === 1) {
            return $data->data[0]->id;
        }

        throw new \RuntimeException('Invalid label: '.$label);
    }

    public function getDeployIdForEnvAndAppLabels(string $accessToken, string $orgId, string $stackLabel, string $appLabel, string $envLabel)
    {
        $stackId = $this->getStackIdForLabel($accessToken, $stackLabel, $orgId);
        $appId = $this->getAppIdForLabel($accessToken, $appLabel, $stackId);
        $envId = $this->getEnvIdForLabel($accessToken, $envLabel, $stackId);
        $result = $this->deploymentClient->listDeployments(
            $accessToken,
            $envId,
            $appId
        );

        $data = json_decode($result->getBody()->getContents());

        if ($data->{"@count"} === 1) {
            return $data->data[0]->id;
        }

        throw new \RuntimeException("No Deployment found for App {$appLabel} in {$envLabel}");
    }
}
