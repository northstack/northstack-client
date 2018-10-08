<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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

    protected $orgs;

    public function __construct(SappClient $api, OrgsClient $orgs)
    {
        parent::__construct('app:create');
        $this->api = $api;
        $this->orgs = $orgs;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('primaryDomain', InputArgument::REQUIRED, 'Primary Domain')
            ->addArgument('baseFolder', InputArgument::OPTIONAL, 'Folder to create/install to (defaults to current directory)')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Org ID (defaults to value in account.json in the current directory)')
            ->addOption('cluster', null, InputOption::VALUE_REQUIRED, 'Deployment location', 'dev-us-east-1')
            ->addOption('wpAdminUser', null, InputOption::VALUE_REQUIRED, 'Wordpress Admin Username on initial db creation', 'account-user')
            ->addOption('wpAdminPass', null, InputOption::VALUE_REQUIRED, 'Wordpress Admin Password on initial db creation', 'random-value')
            ->addOption('wpAdminEmail', null, InputOption::VALUE_REQUIRED, 'Wordpress Admin Email on initial db creation', 'account-email')
            ->addOption('wpTitle', null, InputOption::VALUE_REQUIRED, 'Wordpress title', "app-name")
            ->addOption('wpIsMultisite', null, InputOption::VALUE_NONE, 'Wordpress is this a multi-site install')
            ->addOption('wpMultisiteSubdomains', null, InputOption::VALUE_NONE, 'Wordpress multi-site subdomains install')
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
        $options = $input->getOptions();
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

        $orgId = $options['orgId'];
        if (empty($orgId))
        {
            $path = getcwd()."/.account.json";
            if (file_exists($path))
            {
                $account = json_decode(file_get_contents($path));
                $orgId = $account->org->id;
            } else {
                $output->writeln('<error>You must provide an Org ID via --orgId the first time you create an app</error>');
                return;
            }
        }

        try {
            $r = $this->api->createApp(
                $this->token->token,
                $args['name'],
                $orgId,
                $options['cluster'],
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

        $install = $this->buildWpInstallArgs($options, $args, $io);

        $this->mkDirIfNotExists($appPath);
        $this->mkDirIfNotExists("{$appPath}/config");
        $this->mkDirIfNotExists("{$appPath}/config/dev");
        $this->mkDirIfNotExists("{$appPath}/config/prod");
        $this->mkDirIfNotExists("{$appPath}/config/test");
        file_put_contents("{$appPath}/config/test/config.json", json_encode(['environment' => 'testing', 'auth-type' => 'standard']));
        $this->mkDirIfNotExists("{$appPath}/app");
        $this->mkDirIfNotExists("{$appPath}/logs");

        $env = [];
        foreach ($data->data as $sapp) {
            $env[$sapp->environment] = $sapp->id;

            switch($sapp->environment)
            {
            case 'prod':
                $config = ['environment' => 'production'];
                $build = ['wordpress-install' => $install];
                $domains = ['domains' => [$args['primaryDomain']]];
                break;
            case 'test':
                $domain = "ns-{$sapp->id}.{$sapp->cluster}-northstack.com";
                $install['url'] = "http://$domain/";
                $config = ['environment' => 'testing', 'auth-type' => 'standard'];
                $build = ['wordpress-install' => $install];
                $domains = ['domains' => [$domain]];
                break;
            case 'dev':
                $domain = "ns-{$sapp->id}.{$sapp->cluster}-northstack.com";
                $install['url'] = "http://$domain/";
                $config = ['environment' => 'development', 'auth-type' => 'standard'];
                $build = ['wordpress-install' => $install];
                $domains = ['domains' => [$domain]];
                break;
            }

            file_put_contents("{$appPath}/config/{$sapp->environment}/config.json", json_encode($config, JSON_PRETTY_PRINT));
            file_put_contents("{$appPath}/config/{$sapp->environment}/build.json", json_encode($build, JSON_PRETTY_PRINT));
            file_put_contents("{$appPath}/config/{$sapp->environment}/domains.json", json_encode($domains, JSON_PRETTY_PRINT));
        }

        file_put_contents("{$appPath}/config/environment.json", json_encode($env));

        $assetPath = dirname(__DIR__, 3).'/assets';
        copy("{$assetPath}/config.json", "{$appPath}/config/config.json");
        copy("{$assetPath}/build.json", "{$appPath}/config/build.json");
    }

    function printSuccess(SymfonyStyle $io, $data, $appPath)
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
                    : "ns-{$sapp->id}.{$sapp->cluster}-northstack.com",
                "{$appPath}/config/{$sapp->environment}"
            ];
       }
        $io->table($headers, $rows);
    }

    protected function buildWpInstallArgs($options, $args, OutputInterface $io)
    {
        if ($options['wpTitle'] === 'app-name') {
            $title = $args['name'];
        } else {
            $title = $args['wpTitle'];
        }


        if ($options['wpAdminUser'] === 'account-user')
        {
            // TODO grab the username of the currently logged in user
            $user = "ns-admin";
            $io->writeln("Wordpress Admin User: $user\n");
        }
        else
        {
            $user = $options['wpAdminUser'];
        }

        if ($options['wpAdminPass'] === 'random-value')
        {
            $pass = bin2hex(random_bytes(16));
            $io->writeln("Wordpress Admin Password: $pass\n");
        }
        else
        {
            $pass = $options['wpAdminPass'];
        }

        if ($options['wpAdminEmail'] === 'account-email')
        {
            [$type, $id] = explode(':',json_decode(base64_decode(explode('.', $this->token->token)[1]))->sub);
            $r = $this->orgs->getUser($this->token->token, $id);
            $currentUser = json_decode($r->getBody()->getContents());
            $email = $currentUser->email;
        }
        else
        {
            $email = $options['wpAdminEmail'];
        }

        $install = [
            'url' => $args['primaryDomain'],
            'title' => $title,
            'admin_user' => $user,
            'admin_pass' => $pass,
            'admin_email' => $email,
            'multisite' => $options['wpIsMultisite'],
            'subdomains' => $options['wpMultisiteSubdomains'],
        ];
        return $install;
    }
}
