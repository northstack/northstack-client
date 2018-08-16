<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeployCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var SappClient
     */
    protected $api;

    public function __construct(SappClient $api, AuthApi $authApi)
    {
        parent::__construct('sapp:deploy');
        $this->api = $api;
        $this->authClient = $authApi;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('baseFolder', InputArgument::REQUIRED, 'Path to root of NorthStack folder (contains folder named after app)')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->debug = true;
        }

        $args = $input->getArguments();

        if (!file_exists($args['baseFolder'])) {
            $output->writeln("<error>Folder {$args['baseFolder']} not found</error>");
            exit(1);
        }

        // find sapp id based on environment/app from environment.json

        // request upload url

        // tarball folder

        // upload to s3

        // merge configs

        // trigger deploy

    }
}
