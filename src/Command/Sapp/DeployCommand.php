<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeployCommand extends AbstractDeployCmd
{
    public function configure()
    {
        $this->setDescription('Northstack App Deploy');
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        [$sappId, $appFolder] = $this->uploadApp($input, $output);

        $environment = $input->getArgument('environment');
        $configs = $this->mergeConfigs($appFolder, $environment);
        // trigger deploy
        try
        {
            $r = $this->sappClient->deploy(
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
            /** @noinspection ForgottenDebugOutputInspection */
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

    protected function commandName(): string
    {
        return 'app:deploy-legacy';
    }
}
