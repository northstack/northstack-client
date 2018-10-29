<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;

use NorthStack\NorthStackClient\AppTypes\BaseType;
use NorthStack\NorthStackClient\AppTypes\WordPressType;

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
    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;

    public function __construct(SappClient $api, OrgsClient $orgs, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct('app:create');
        $this->api = $api;
        $this->orgs = $orgs;
        $this->orgAccountHelper = $orgAccountHelper;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('primaryDomain', InputArgument::REQUIRED, 'Primary Domain')
            ->addArgument('appType', InputArgument::OPTIONAL, 'App Type', 'WORDPRESS')
            ->addArgument('baseFolder', InputArgument::OPTIONAL, 'Folder to create/install to (defaults to current directory)')
            ->addOption('cluster', null, InputOption::VALUE_REQUIRED, 'Deployment location', 'dev-us-east-1')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Application type (one of: [wordpress, static])', 'wordpress')
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

        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];

        $appTemplate = null;
        $templateArgs = [
            'appName' => $args['name'],
            'baseDir' => $appPath,
            'primaryDomain' => $args['primaryDomain'],
            'cluster' => $options['cluster']
        ];
        $questionHelper = $this->getHelper('question');

        // TODO: move this logic somewhere else
        switch($options['type']) {
            case 'wordpress':
                $appTemplate = new WordPressType($input, $output, $questionHelper, $templateArgs);
                break;
            default:
                $appTemplate = new BaseType($input, $output, $questionHelper, $templateArgs);
        }

        $appTemplate->promptForArgs(); exit;

        try {
            $r = $this->api->createApp(
                $this->token->token,
                $args['name'],
                $orgId,
                $options['cluster'],
                $args['primaryDomain'],
                $args['appType']
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
        file_put_contents('./resp.json', json_encode($data, JSON_PRETTY_PRINT));
        $appTemplate->writeConfigs($data->data);
        $this->printSuccess($io, $data, $appPath);
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
}
