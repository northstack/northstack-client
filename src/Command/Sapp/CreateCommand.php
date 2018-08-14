<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        parent::__construct('sapp:create');
        $this->api = $api;
        $this->authClient = $authApi;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('orgId', InputArgument::REQUIRED, 'Org ID')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, dev)')
            ->addArgument('cluster', InputArgument::REQUIRED, 'Cluster name')
            ->addArgument('primaryDomain', InputArgument::REQUIRED, 'Primary Domain')
            ->addArgument('baseFolder', InputArgument::REQUIRED, 'Folder to create/install to', '.')
            ->addOption('altdomain', 'd', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Extra domains')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'JSON blob of configuration')
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
        $domains = $input->getOption('altdomain') ?
            json_encode($input->getOption('altdomain')) :
            null;
        $config = $input->getOption('config');

        try {
            $r = $this->api->createApp(
                $this->token->token,
                $args['name'],
                $args['orgId'],
                $args['cluster'],
                $args['primaryDomain'],
                $domains,
                $config
            );
        } catch (ClientException $e) {
            $output->writeln('<error>App Create Failed</error>');
            $output->writeln($e->getResponse()->getBody()->getContents());
        }

        $data = json_decode($r->getBody()->getContents());
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));

        // create folder structure
        $baseFolder = $input->getArgument('baseFolder');
        if ($baseFolder === '.') {
            $baseFolder = getcwd();
        } elseif (!file_exists($baseFolder)) {
            $this->mkDirIfNotExists($baseFolder);
        }

        $nsdir = $baseFolder;
        if (!file_exists("{$nsdir}/account.json")) {
            file_put_contents("{$nsdir}/account.json", json_encode(['orgId' => $args['orgId']], JSON_PRETTY_PRINT));
        }

        $appPath = "{$nsdir}/{$args['name']}";
        if (file_exists($appPath)) {
            $output->writeln("Folder for app {$args['name']} already exists at {$nsdir}/{$args['name']}");
        } else {
            $this->mkDirIfNotExists($appPath);
            $this->mkDirIfNotExists("{$appPath}/config");
            $this->mkDirIfNotExists("{$appPath}/config/dev");
            $this->mkDirIfNotExists("{$appPath}/config/prod");
            $this->mkDirIfNotExists("{$appPath}/config/test");
            $this->mkDirIfNotExists("{$appPath}/app");
            $this->mkDirIfNotExists("{$appPath}/logs");

            $assetPath = dirname(__DIR__, 3).'/assets';
            copy("{$assetPath}/config.json", "{$appPath}/config/config.json");
            copy("{$assetPath}/build.json", "{$appPath}/config/build.json");
            copy("{$assetPath}/domains.json", "{$appPath}/config/domains.json");
        }
    }
}
