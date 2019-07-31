<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Helpers\OutputFormatterTrait;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StackListCommand extends Command
{
    use OauthCommandTrait;
    use OutputFormatterTrait;

    /**
     * @var StackClient
     */
    private $stackClient;
    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;
    /**
     * @var OrgsClient
     */
    private $orgsClient;

    public function __construct(OrgsClient $orgsClient, StackClient $stackClient, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct("stack:list");
        $this->stackClient = $stackClient;
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('List Stacks')
            ->addOption('label', 'l', InputOption::VALUE_REQUIRED, 'Label to filter by')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $this->requireLogin($this->orgsClient);

        $r = $this->stackClient->listStacks(
            $this->token->token,
            $orgId,
            $input->getOption('label')
        );

        $this->displayTable($output, json_decode($r->getBody()->getContents(), true), [
            'ID' => 'id',
            'Label' => 'label',
            'Type' => 'type',
            'Last Updated' => 'updated',
        ]);
    }
}
