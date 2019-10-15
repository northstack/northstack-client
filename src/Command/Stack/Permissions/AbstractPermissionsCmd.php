<?php


namespace NorthStack\NorthStackClient\Command\Stack\Permissions;

use NorthStack\NorthStackClient\API\Infra\AppClient;
use NorthStack\NorthStackClient\API\Infra\PermissionsClient;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Helpers\OutputFormatterTrait;
use NorthStack\NorthStackClient\Command\Helpers\ValidationErrors;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\StackCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;

abstract class AbstractPermissionsCmd extends Command
{
    use OauthCommandTrait;
    use StackCommandTrait;
    use OutputFormatterTrait;
    use ValidationErrors;

    /**
     * @var OrgAccountHelper
     */
    protected $orgAccountHelper;
    /**
     * @var OrgsClient
     */
    protected $orgsClient;
    /**
     * @var PermissionsClient
     */
    protected $permissionsClient;
    protected $commandLabel;

    public function __construct(
        OrgsClient $orgsClient,
        StackClient $stackClient,
        AppClient $appClient,
        OrgAccountHelper $orgAccountHelper,
        PermissionsClient $permissionsClient
    )
    {
        parent::__construct($this->commandLabel);
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->stackClient = $stackClient;
        $this->appClient = $appClient;
        $this->permissionsClient = $permissionsClient;
    }
}
