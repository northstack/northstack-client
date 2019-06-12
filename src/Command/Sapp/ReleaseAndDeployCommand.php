<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
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
    )
    {
        parent::__construct($api, $authApi, $guzzle, $archiver);
        $this->deployClient = $deployClient;
    }

    public function configure()
    {
        $this->setDescription('Use multi-stage deploy (release notes can be piped to this command)');
        $this->addArgument('title', InputArgument::REQUIRED, 'Release Title');
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Archiving app folder and uploading to S3...');
        [$sappId, $appFolder] = $this->uploadApp($input, $output);

        $environment = $input->getArgument('environment');
        $configs = $this->mergeConfigs($appFolder, $environment);

        $appType = json_decode($configs['config'])->app_type;

        // update configs
        $output->writeln('Updating Sapp configs...');
        $update = [
            'configBuild' => $configs['build'],
            'domains' => $configs['domains'],
            'config' => $configs['config'],
        ];
        // Since gateway configs are optional...
        if (isset($configs['gateway'])) {
            $update['configGatewayCust'] = $configs['gateway'];
        }

        try {
            /**
             * TODO: check for shared configs here -- if this isn't the prod sapp,
             * we should confirm that the user wants to actually make those shared changes
             * (and note that a deploy of each sapp individually is required)
             *
             * -- actually, just have a check that asks if the user also wants to push sharedconfigs too
             * (and then have it so they don't push by default)
             */
            $this->sappClient->update($this->token->token, $sappId, $update);
        } catch (RequestException $e) {
            if (422 === $e->getCode()) {
                $errors = json_decode($e->getResponse()->getBody()->getContents(), true)['body'];
                // Prettfy any validation errors
                foreach ($errors as $field => $error) {
                    foreach ($error['messages'] as $errorMessage) {
                        if (false !== stripos($errorMessage, '$schema(')) {
                            $errorMessage = explode(PHP_EOL, json_decode($errorMessage));
                            // if for some reason we didn't get <2 items in the explode, have a fallback:
                            $errorMessage = !empty($errorMessage[1]) ? trim($errorMessage[1]) : implode(' ', $errorMessage);
                        }

                        $this->printBlock($output, ["Validation error at '$field' in $errorMessage"]);
                    }
                }

                exit;
            }

            throw $e;
        }

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
            $output->writeln('Could not generate an initial build. - ' . $e->getMessage());
            exit(1);
        }

        $release = json_decode($result->getBody()->getContents());

        try {
            $output->writeln('Updating Gateway...');
            $this->deployClient->gateway($this->token->token, $release->id);
        } catch (ClientException $e) {
            $output->writeln('Could not update gateway. - ' . $e->getMessage());
            exit(1);
        }

        // nothing left to do for static apps
        if (in_array($appType, ['STATIC', 'JEKYLL'])) {
            $output->writeln('Release and Deploy Finished!');
            return;
        }

        try {
            $output->writeln('Running Single Worker...');
            $this->deployClient->run($this->token->token, $release->id);
        } catch (ClientException $e) {
            $output->writeln('Failed to start worker. - ' . $e->getMessage());
            exit(1);
        }

        $output->writeln('Testing worker health (this might take a while)');
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
                $output->writeln('<error>We were not able to connect to the worker.</error>');
                exit(1);
            case 'HEALTHY':
                $output->writeln('<info>Worker is running properly!</info>');
                break;
            case 'UNHEALTHY':
                $output->writeln('<comment>Worker is reporting as unhealthy.</comment>');
                exit(1);
        }

        try {
            $output->writeln('Stopping old workers...');
            $this->deployClient->stopOld($this->token->token, $release->id);
        } catch (ClientException $e) {
            $output->writeln('Failed to stop all old workers. - ' . $e->getMessage());
            exit(1);
        }

        $output->writeln('Release and Deploy Finished!');
    }

    protected function testWorker(string $releaseId)
    {
        try {
            $result = $this->deployClient->test($this->token->token, $releaseId);
            $status = json_decode($result->getBody()->getContents());
            if ($status === 'PASSING') {
                return 'HEALTHY';
            }
            return $status;
        } catch (ClientException $e) {
            return 'UNHEALTHY';
        }
    }

    protected function commandName(): string
    {
        return 'app:deploy';
    }
}
