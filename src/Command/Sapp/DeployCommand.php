<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\JSON\Merger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function __construct(
        SappClient $api,
        AuthApi $authApi,
        Client $guzzle
    )
    {
        parent::__construct('app:deploy');
        $this->api = $api;
        $this->authClient = $authApi;
        $this->guzzle = $guzzle;
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
        exec("cd $appFolder && tar cvzf {$zip} app");

        // upload to s3
        $this->guzzle->put($uploadUrl, [
            'multipart' => [
                [
                    'name'     => 'file',
                    'contents' => fopen($zip, 'rb'),
                    'filename' => "{$sappId}.tar.gz",
                ],
            ],
        ]);
        unlink($zip);

        // merge configs
        $configs = [
            'config.json' => file_get_contents("{$appFolder}/config/config.json"),
            'build.json' => file_get_contents("{$appFolder}/config/build.json"),
            'domains.json' => [],
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
                ($configs['config.json']),
                ($configs['build.json']),
                ($configs['domains.json'])
            );
        }
        catch(ClientException $e)
        {
            $r = $e->getResponse();
        }

        $output->writeln("Deploy finished code: ".$r->getStatusCode());
        print_r(json_decode($r->getBody()->getContents()));
    }
}
