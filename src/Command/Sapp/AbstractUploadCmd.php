<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use GuzzleHttp\Client;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Build\Archiver;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractUploadCmd extends Command
{
    use SappEnvironmentTrait;
    use OauthCommandTrait;
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

        if (empty($args['baseFolder'])) {
            $args['baseFolder'] = getcwd();
        }

        [$sappId, $appFolder] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment'],
            $args['baseFolder']
        );

        // request upload url
        $r = $this->sappClient->requestDeploy($this->token->token, $sappId);
        $r = json_decode($r->getBody()->getContents());
        $uploadUrl = $r->uploadUrl;
        try {
            $zip = $this->archiver->archive($args['baseFolder'], $sappId, $appFolder);
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
}
