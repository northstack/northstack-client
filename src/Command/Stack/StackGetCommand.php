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

class StackGetCommand extends Command
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
        parent::__construct("stack:get");
        $this->stackClient = $stackClient;
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Get Stack')
            ->addArgument('label')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $this->requireLogin($this->orgsClient);

        $r = $this->stackClient->listStacks(
            $this->token->token,
            $orgId
        );

        $body = json_decode($r->getBody()->getContents(), true);

        foreach ($body['data'] as $stack) {
            if ($stack['label'] === $input->getArgument('label')) {
                $this->displayRecord($output, $stack);
                return;
            }
        }

        $output->writeln('<warning>Stack not found</warning>');
    }
}
