<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Northstack\DeployClient;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Build\Archiver;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseAndDeployCommand extends AbstractDeployCmd
{
    /**
     * @var DeployClient
     */
    protected $deployClient;

    public function __construct(
        SappClient $api,
        AuthApi $authApi,
        Client $guzzle,
        Archiver $archiver,
        DeployClient $deployClient
    ) {
        parent::__construct($api, $authApi, $guzzle, $archiver);
        $this->deployClient = $deployClient;
    }

    public function configure()
    {
        $this->setDescription('Use multi-stage deploy (release notes can be piped to this command)');
        $this->addArgument('title', InputArgument::REQUIRED, 'Release Title');
        parent::configure();
    }

    protected function commandName(): string
    {
        return 'app:deploy-release';
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Archiving app folder and uploading to S3...');
        [$sappId, $appFolder] = $this->uploadApp($input, $output);

        $environment = $input->getArgument('environment');
        $configs = $this->mergeConfigs($appFolder, $environment);

        // update configs
        $output->writeln('Updating Sapp configs...');
        $this->sappClient->update($this->token->token, $sappId, [
            'configBuild' => $configs['build.json'],
            'domains' => $configs['domains.json'],
            'config' => $configs['config.json'],
        ]);

        // proceed with the rest of the deploy via northstack api
        $notes = null;
        // check if we piped some release notes
        if (0 === ftell(STDIN)) {
            $notes = stream_get_contents(STDIN);
        }

        try {
            $output->writeln('Building container...');
            $result = $this->deployClient->build($this->token->token, $sappId, $input->getArgument('title'), $notes);
        } catch (ClientException $e) {
            $output->writeln('Could not generate an initial build. - '.$e->getMessage());
            exit(1);
        }

        $release = json_decode($result->getBody()->getContents());

        try {
            $output->writeln('Updating Gateway...');
            $this->deployClient->gateway($this->token->token, $release->id);
        } catch (ClientException $e) {
            $output->writeln('Could not update gateway. - '.$e->getMessage());
            exit(1);
        }

        try {
            $output->writeln('Running Single Worker...');
            $this->deployClient->run($this->token->token, $release->id);
        } catch (ClientException $e) {
            $output->writeln('Failed to start worker. - '.$e->getMessage());
            exit(1);
        }

        $output->writeln('Testing worker health (might take a while)');
        $healthStatus = 'UNKNOWN';

        $retries = 0;
        while ($healthStatus === 'UNKNOWN' && $retries < 20) {
            $output->write('.');
            sleep(5);
            $healthStatus = $this->testWorker($release->id);
            $retries++;
        }
        $output->writeln('');

        switch ($healthStatus) {
            case 'UNKNOWN':
                $output->writeln('We were not able to connect to the worker.');
                exit(1);
            case 'HEALTHY':
                $output->writeln('Worker is running properly!');
                break;
            case 'UNHEALTHY':
                $output->writeln('Worker is reporting as unhealthy.');
                exit(1);
        }

        try {
            $output->writeln('Stopping old workers...');
            $this->deployClient->stopOld($this->token->token, $release->id);
        } catch (ClientException $e) {
            $output->writeln('Failed to stop all old workers. - '.$e->getMessage());
            exit(1);
        }

        $output->writeln('Release and Deploy Finished!');
    }

    protected function testWorker(string $releaseId) {
        try {
            $result = $this->deployClient->test($this->token->token, $releaseId);
            if ($result->getStatusCode() === 102) {
                return 'UNKNOWN';
            }
            return 'HEALTHY';
        } catch (ClientException $e) {
            return 'UNHEALTHY';
        }
    }
}
