<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var SappClient
     */
    protected $api;

    public function __construct(SappClient $api, AuthApi $authApi)
    {
        parent::__construct('app:create');
        $this->api = $api;
        $this->authClient = $authApi;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('primaryDomain', InputArgument::REQUIRED, 'Primary Domain')
            ->addArgument('baseFolder', InputArgument::OPTIONAL, 'Folder to create/install to (defaults to current directory)')
            ->addArgument('orgId', InputArgument::OPTIONAL, 'Org ID (defaults to value in accounts.json in the current directory)')
            ->addArgument('cluster', InputArgument::OPTIONAL, 'cluster', 'ns-dev-us-east-1')
        ;
        $this->addOauthOptions();
    }

    protected function mkDirIfNotExists($path) {
        if (
            !file_exists($path) &&
            !mkdir($concurrentDirectory = $path) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created',
                $concurrentDirectory));
        }
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->debug = true;
        }

        $args = $input->getArguments();

        // create folder structure
        $nsdir = $input->getArgument('baseFolder');
        if ($nsdir === '.' || empty($nsdir)) {
            $nsdir = getcwd();
        } elseif (!file_exists($nsdir)) {
            $this->mkDirIfNotExists($nsdir);
        }

        $appPath = "{$nsdir}/{$args['name']}";

        if (file_exists($appPath)) {
            $output->writeln("Folder for app {$args['name']} already exists at {$appPath}");
            return;
        }

        try {
            $r = $this->api->createApp(
                $this->token->token,
                $args['name'],
                $args['orgId'],
                $args['cluster'],
                $args['primaryDomain']
            );
        } catch (ClientException $e) {
            $output->writeln('<error>App Create Failed</error>');
            $output->writeln($e->getResponse()->getBody()->getContents());
            return;
        }

        $data = json_decode($r->getBody()->getContents());
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));

        $this->mkDirIfNotExists($appPath);
        $this->mkDirIfNotExists("{$appPath}/config");
        $this->mkDirIfNotExists("{$appPath}/config/dev");
        file_put_contents("{$appPath}/config/dev/config.json", json_encode(['environment' => 'development', 'auth-type' => 'standard']));
        $this->mkDirIfNotExists("{$appPath}/config/prod");
        file_put_contents("{$appPath}/config/prod/config.json", json_encode(['environment' => 'production']));
        $this->mkDirIfNotExists("{$appPath}/config/test");
        file_put_contents("{$appPath}/config/test/config.json", json_encode(['environment' => 'testing', 'auth-type' => 'standard']));
        $this->mkDirIfNotExists("{$appPath}/app");
        $this->mkDirIfNotExists("{$appPath}/logs");

        $env = [];
        foreach ($data->data as $sapp) {
            $env[$sapp->environment] = $sapp->id;
        }
        file_put_contents("{$appPath}/config/environment.json", json_encode($env));

        $assetPath = dirname(__DIR__, 3).'/assets';
        copy("{$assetPath}/config.json", "{$appPath}/config/config.json");
        copy("{$assetPath}/build.json", "{$appPath}/config/build.json");
        copy("{$assetPath}/domains.json", "{$appPath}/config/domains.json");
    }
}
