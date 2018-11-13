<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Northstack\NorthstackClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

class LaunchCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;
    /**
     * @var NorthstackClient
     */
    protected $api;
    /**
     * @var Client
     */
    private $guzzle;

    public function __construct(
        NorthstackClient $api,
        AuthApi $authApi,
        Client $guzzle
    )
    {
        parent::__construct('app:debug:launch-worker');
        $this->api = $api;
        $this->authClient = $authApi;
        $this->guzzle = $guzzle;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Launch a worker for an App')
            ->setHelp('Northstack will launch additional workers for you as needed.  This command can be used for debugging, or to prime additional capacity.  Note that unused capacity will shutoff after the inactivity period.')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $args = $input->getArguments();

        [$sappId, $appFolder] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment']
        );

        try
        {
            $r = $this->api->launchWorker(
                $this->token->token,
                $sappId
            );
        }
        catch(ClientException $e)
        {
            $r = $e->getResponse();
        }

        $output->writeln("Launch finished code: ".$r->getStatusCode());
        print_r(json_decode($r->getBody()->getContents()));
    }
}
