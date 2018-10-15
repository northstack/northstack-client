<?php


namespace NorthStack\NorthStackClient\Command\Signup;


use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var OrgsClient
     */
    protected $api;
    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;

    public function __construct(OrgsClient $api, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct('signup:verify');
        $this->api = $api;
        $this->orgAccountHelper = $orgAccountHelper;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('NorthStack Signup Phone Verification')
            ->addArgument('code', InputArgument::REQUIRED, 'Code sent via text message')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Org ID (if you have access to multiple orgs)')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!($orgId = $input->getOption('orgId'))) {
            $orgId = $this->orgAccountHelper->getDefaultOrg()['id'];
        }
        $this->api->verify($this->token->token, $orgId, $input->getArgument('code'));

        $output->writeln('Verified!');
    }
}
