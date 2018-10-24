<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\JSON\Merger;
use NorthStack\NorthStackClient\PathHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;
    /**
     * @var SappClient
     */
    protected $api;
    /**
     * @var Client
     */
    private $guzzle;
    /**
     * @var Merger
     */
    private $merger;

    /**
     * @var PathHelper
     */
    private $pathHelper;

    public function __construct(
        SappClient $api,
        AuthApi $authApi,
        Client $guzzle,
        PathHelper $pathHelper
    )
    {
        parent::__construct('app:deploy');
        $this->api = $api;
        $this->authClient = $authApi;
        $this->guzzle = $guzzle;
        $this->pathHelper = $pathHelper;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
            ->addArgument('baseFolder', InputArgument::OPTIONAL, 'Path to root of NorthStack folder (contains folder named after app)')
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

        if (empty($args['baseFolder']))
            $args['baseFolder'] = getcwd();

        $args['baseFolder'] = $this->pathHelper->validPath($args['baseFolder']);

        [$sappId, $appFolder] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment'],
            $args['baseFolder']
        );

        // request upload url
        $r = $this->api->requestDeploy($this->token->token, $sappId);
        $r = json_decode($r->getBody()->getContents());
        $uploadUrl = $r->uploadUrl;

        // tarball folder
        $zip = "{$args['baseFolder']}/{$sappId}.tar.gz";
        $tarFile = escapeshellarg($zip);
        $tarFolder = escapeshellarg($appFolder."/app");
        $cmd = "tar -C {$tarFolder} -cvzf {$tarFile} .";
        exec($cmd, $out, $ret);
        if ($ret !== 0)
        {
            $output->writeln([
                "<error>Uh oh, something went wrong while preparing the app for deploy</error>",
                "Command: {$cmd}",
                "Exit code: {$ret}",
            ]);
            exit(1);
        }

        // upload to s3
        $this->guzzle->put($uploadUrl, [ 'body' => fopen($zip, 'rb') ]);
        unlink($zip);

        // merge configs
        $configs = [
            'config.json' => file_get_contents("{$appFolder}/config/config.json"),
            'build.json' => file_get_contents("{$appFolder}/config/build.json"),
            'domains.json' => '{}',
        ];
        foreach ($configs as $file => $json) {
            $envFile = "{$appFolder}/config/{$args['environment']}/{$file}";
            if (file_exists($envFile)) {
                $configs[$file] = Merger::merge($json, file_get_contents($envFile));
            }
            else {
                $configs[$file] = Merger::merge($json, '{}');
            }
        }
        // trigger deploy
        try
        {
            $r = $this->api->deploy(
                $this->token->token,
                $sappId,
                $configs['config.json'],
                $configs['build.json'],
                $configs['domains.json']
            );
        }
        catch(ClientException $e)
        {
            $r = $e->getResponse();
        }

        $output->writeln("Deploy finished code: ".$r->getStatusCode());
        $body = json_decode($r->getBody()->getContents());
        if ($r->getStatusCode() !== 200)
        {
            print_r($body);
            $output->writeln("Deploy failed");
            exit(1);
        }

        $io = new SymfonyStyle($input, $output);

        $app = $body->sapp;
        $io->writeln("App Info");
        $headers = ['Field', 'Value'];
        $rows = [
            ['Name', $app->name],
            ['Cluster', $app->cluster],
            ['Id', $app->id],
            ['OrgId', $app->orgId],
            ['Parent', $app->parentSapp],
            ['Env', $app->environment],
            ['Domains', implode("\n",$app->domains->domains)],
        ];

        $io->table($headers, $rows);


        $io->writeln("Build Status:");
        $headers = ['Field', 'Value'];
        $started = @$body->deploy->builder->started;
        $stopped = @$body->deploy->builder->stopped;
        $length = $started && $stopped ? strtotime($stopped) - strtotime($started) : '';
        $rows = [
            ['Task Arn', @$body->deploy->builder->taskArn],
            ['Started', $started],
            ['Stopped', $stopped],
            ['Length', $length],
        ];

        $io->table($headers, $rows);

        if (!$stopped || isset($body->deploy->builder->failure)) {
            $io->writeln('<error>Build failed</error>');
            return;
        }

        $io->writeln("Worker Status");
        $headers = ['Field', 'Value'];
        $rows = [
            ['Task Arn', $body->deploy->worker->taskArn],
            ['Started', $body->deploy->worker->started],
        ];

        $io->table($headers, $rows);
    }
}
