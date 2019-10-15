<?php


namespace NorthStack\NorthStackClient\Command\Stack;


use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Infra\AppClient;
use NorthStack\NorthStackClient\API\Infra\StackClient;
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

class AppCreateCommand extends Command
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

    public function __construct(
        OrgsClient $orgsClient,
        StackClient $stackClient,
        AppClient $appClient,
        OrgAccountHelper $orgAccountHelper
    )
    {
        parent::__construct("stack:app:create");
        $this->orgAccountHelper = $orgAccountHelper;
        $this->orgsClient = $orgsClient;
        $this->stackClient = $stackClient;
        $this->appClient = $appClient;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Create New Stack App')
            ->addArgument('stack', InputArgument::REQUIRED, 'Stack label')
            ->addArgument('app', InputArgument::REQUIRED, 'App label')
            ->addArgument('type', InputArgument::REQUIRED, 'One of "WORDPRESS", "STATIC", "JEKYLL", or "GATSBY"')
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
            $r = $this->appClient->createApp(
                $this->token->token,
                $stackId,
                $input->getArgument('type'),
                $input->getArgument('app')
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
