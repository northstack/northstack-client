<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use NorthStack\NorthStackClient\API\Infra\ResourceClient;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Helpers\OutputFormatterTrait;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\StackCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResourceListCommand extends Command
{
    use OauthCommandTrait;
    use StackCommandTrait;
    use OutputFormatterTrait;

    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;
    /**
     * @var OrgsClient
     */
    private $orgsClient;
    /**
     * @var ResourceClient
     */
    private $resourceClient;

    public function __construct(OrgsClient $orgsClient, StackClient $stackClient, ResourceClient $resourceClient, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct("stack:resource:list");
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->stackClient = $stackClient;
        $this->resourceClient = $resourceClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('List Resources for Stack')
            ->addArgument('stack', InputArgument::REQUIRED, 'Stack label')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $this->requireLogin($this->orgsClient);

        $stackId = $this->getStackIdForLabel(
            $this->token->token,
            $input->getArgument('stack'),
            $orgId
        );

        $r = $this->resourceClient->listResources(
            $this->token->token,
            $stackId
        );

        $body = json_decode($r->getBody()->getContents(), true);

        $this->displayTable($output, $body->data, ['Label' => 'label', 'Type' => 'type']);
    }
}
