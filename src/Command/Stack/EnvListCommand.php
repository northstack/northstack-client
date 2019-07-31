<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Infra\StackEnvClient;
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

class EnvListCommand extends Command
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

    public function __construct(OrgsClient $orgsClient, StackClient $stackClient, StackEnvClient $envClient, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct("stack:env:list");
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->envClient = $envClient;
        $this->stackClient = $stackClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('List Environments for Stack')
            ->addArgument('stack', InputArgument::REQUIRED, 'Stack label')
            ->addOption('label', 'l', InputOption::VALUE_REQUIRED, 'Environment label for filtering')
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

        $r = $this->envClient->listEnvironments(
            $this->token->token,
            $stackId,
            $input->getOption('label') ?: null
        );

        $body = json_decode($r->getBody()->getContents(), true);

        $this->displayTable($output, $body->data, ['Label' => 'label', 'Region' => 'region']);
    }
}
