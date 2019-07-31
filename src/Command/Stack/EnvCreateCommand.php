<?php


namespace NorthStack\NorthStackClient\Command\Stack;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use NorthStack\NorthStackClient\API\Infra\StackEnvClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Helpers\OutputFormatterTrait;
use NorthStack\NorthStackClient\Command\Helpers\ValidationErrors;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\StackCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EnvCreateCommand extends Command
{
    use OauthCommandTrait;
    use StackCommandTrait;
    use OutputFormatterTrait;
    use ValidationErrors;

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
        parent::__construct("stack:env:add");
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->envClient = $envClient;
        $this->stackClient = $stackClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create New Stack Environments')
            ->addArgument('stack', InputArgument::REQUIRED, 'Stack label')
            ->addArgument('label', InputArgument::REQUIRED, 'Environment label (no whitespace, only letters & numbers)')
            ->addArgument('region', InputArgument::OPTIONAL, 'AWS Region for environment (defaults to us-east-1)', 'us-east-1')
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

        try {
            $r = $this->envClient->createEnvironment(
                $this->token->token,
                $stackId,
                $input->getArgument('region'),
                $input->getArgument('label')
            );
            $this->displayRecord($output, json_decode($r->getBody()->getContents(), true));
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 422) {
                $this->displayValidationErrors($e->getResponse(), $output);
            } else {
                throw $e;
            }
        }
    }
}
