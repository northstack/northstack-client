<?php


namespace NorthStack\NorthStackClient\Command\Signup;


use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerifyCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var OrgsClient
     */
    protected $api;

    public function __construct(OrgsClient $api)
    {
        parent::__construct('verify');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('NorthStack Signup Phone Verification')
            ->addArgument('orgId', InputArgument::REQUIRED, 'Organization ID from signup')
            ->addArgument('code', InputArgument::REQUIRED, 'Code sent via text message');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->api->verify($this->token->token, $input->getArgument('orgId'), $input->getArgument('code'));

        $output->writeln('Verified!');
    }
}
