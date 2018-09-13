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
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addArgument('cluster', InputArgument::OPTIONAL, 'cluster', 'dev-us-east-1')
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
            $this->api->setDebug(true);
        }

        $args = $input->getArguments();
        $io = new SymfonyStyle($input, $output);

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
            $i = $e->getResponse()->getStatusCode();
            if ($i === 401) {
                $output->writeln('<error>Please Log in and try again</error>');
            } else {
                $output->writeln('<error>App Create Failed</error>');
                $output->writeln($e->getResponse()->getBody()->getContents());
            }
            return;
        }

        $data = json_decode($r->getBody()->getContents());
        $this->printSuccess($io, $data, $appPath);

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

            if ($sapp->environment != 'prod')
            {
                file_put_contents("{$appPath}/config/{$sapp->environment}/domains.json", json_encode(
                    ['domains' => ["ns-{$sapp->id}.{$sapp->environment}.northstack.com"]]
                ));
            }
        }
        file_put_contents("{$appPath}/config/environment.json", json_encode($env));

        file_put_contents("{$appPath}/config/prod/domains.json", json_encode(
            ['domains' => [$args['primaryDomain']]]
        ));

        $assetPath = dirname(__DIR__, 3).'/assets';
        copy("{$assetPath}/config.json", "{$appPath}/config/config.json");
        copy("{$assetPath}/build.json", "{$appPath}/config/build.json");
    }

    function printSuccess($io, $data, $appPath)
    {
        $sapps = $data->data;
        $appName = $sapps[0]->name;
        $io->writeln("Woohoo! Your NorthStack instance ({$appName}) was created successfully. Here are your prod, testing, and dev apps:");

        $headers = ['id', 'environment', 'fqdn', 'config path'];
        $rows = [];
        foreach ($sapps as $sapp)
        {
            $rows[] = [
                $sapp->id,
                $sapp->environment,
                ($sapp->parentSapp === null)
                    ? $sapp->primaryDomain
                    : "ns-{$sapp->id}.{$sapp->environment}.northstack.com",
                "{$appPath}/config/{$sapp->environment}"
            ];
       }
        $io->table($headers, $rows);
    }
}
