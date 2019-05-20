<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\RequestException;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FetchCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;
    use CommandFetchAppTrait;
    /**
     * @var SappClient
     */
    protected $api;

    public function __construct(
        SappClient $api
    )
    {
        parent::__construct('app:fetch');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Fetch info about an App and download configs for local development')
            ->addArgument('appId', InputArgument::REQUIRED, 'Parent App ID')
            ->addOption('appSlug', null, InputOption::VALUE_REQUIRED, 'Name to use for the app\'s local directory and local reference')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $this->api->setDebug();
        }


        $questionHelper = $this->getHelper('question');
        $appSlug = $input->getOption('appSlug');
        if (!$appSlug) {
            $appSlug = $this->getLocalAppSlug($input->getArgument('name'));
            $output->writeln('No app slug set. The local app\'s slug will be: ' . $appSlug);
        } else {
            $this->getLocalAppSlug($appSlug);
        }

        $appPath = $this->getLocalAppDir($input, $output, $questionHelper, $appSlug);

        $output->writeln('Fetching app...');
        try {
        $r = $this->api->getAppBySappId($this->token->token, $input->getArgument('appId'));
            $app = json_decode($r->getBody()->getContents());
        } catch (RequestException $e) {
            if (403 === $e->getCode() || 404 === $e->getCode()) {
                $output->writeln('<error>Unable to fetch app. The app either does not exist, or you do not have access to it.</error>');
                exit;
            }

            throw $e;
        }

        $output->writeln('Successfully fetched app info from API. Continuing...');

        $this->setupLocalApp($input, $output, $app, $appSlug, $appPath);
        $output->writeln("Success! Local configs for ({$app->appName}) were created successfully.");
        $this->printSuccess($input, $output, $app, $appSlug, $appPath, false);


    }
}
