<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use GuzzleHttp\Client;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Build\Archiver;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\AutoLoginTrait;
use NorthStack\NorthStackClient\JSON\Merger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDeployCmd extends Command
{
    use SappEnvironmentTrait;
    use OauthCommandTrait;
    use AutoLoginTrait;
    /**
     * @var SappClient
     */
    protected $sappClient;
    /**
     * @var Client
     */
    protected $guzzle;
    /**
     * @var Archiver
     */
    protected $archiver;
    /**
     * @var AuthApi
     */
    protected $authClient;

    public function __construct(
        SappClient $api,
        AuthApi $authApi,
        Client $guzzle,
        Archiver $archiver
    )
    {
        parent::__construct($this->commandName());
        $this->sappClient = $api;
        $this->authClient = $authApi;
        $this->guzzle = $guzzle;
        $this->archiver = $archiver;
    }

    abstract protected function commandName(): string;

    public function configure()
    {
        parent::configure();
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
        ;
        $this->addOauthOptions();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return array
     */
    protected function uploadApp(InputInterface $input, OutputInterface $output): array
    {
        if ($output->isDebug())
        {
            $this->sappClient->setDebug(true);
        }

        $args = $input->getArguments();

        [$sappId, $appFolder] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment']
        );

        // request upload url
        $r = $this->sappClient->requestDeploy($this->token->token, $sappId);
        $r = json_decode($r->getBody()->getContents());
        $uploadUrl = $r->uploadUrl;
        try {
            $zip = $this->archiver->archive($sappId, $appFolder);
        } catch (\Throwable $e) {
            $output->writeln([
                "<error>Uh oh, something went wrong while preparing the app for deploy</error>",
                "Message: {$e->getMessage()}",
            ]);
            exit(1);
        }

        // upload to s3
        $this->guzzle->put($uploadUrl, ['body' => fopen($zip, 'rb')]);
        unlink($zip);

        return [$sappId, $appFolder];
    }

    protected function mergeConfigs(string $appFolder, string $environment)
    {
        // merge configs
        $configs = [
            'config.json' => file_get_contents("{$appFolder}/config/config.json"),
            'build.json' => file_get_contents("{$appFolder}/config/build.json"),
            'domains.json' => '{}',
        ];
        foreach ($configs as $file => $json) {
            $envFile = "{$appFolder}/config/{$environment}/{$file}";
            if (file_exists($envFile)) {
                $configs[$file] = Merger::merge($json, file_get_contents($envFile));
            } else {
                $configs[$file] = Merger::merge($json, '{}');
            }
        }

        return $configs;
    }
}
