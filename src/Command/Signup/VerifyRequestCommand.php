<?php


namespace NorthStack\NorthStackClient\Command\Signup;


use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyRequestCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var OrgsClient
     */
    protected $api;

    public function __construct(OrgsClient $api)
    {
        parent::__construct('signup:reverify');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('NorthStack Signup Phone Verification (re-request a text)')
            ->addArgument('orgId', InputArgument::REQUIRED, 'Organization ID from signup');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->api->verifyRequest($this->token->token, $input->getArgument('orgId'));

        $output->writeln('Check your phone!');
    }
}
