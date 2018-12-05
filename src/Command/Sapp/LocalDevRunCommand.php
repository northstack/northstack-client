<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\Command\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class LocalDevRunCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'Run a docker-compose command';

    protected function commandName(): string {
        return 'app:localdev:run';
    }

    public function configure()
    {
        parent::configure();
        $this
            ->addArgument('run', InputArgument::IS_ARRAY, 'Command to run', ['help'])
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $input->getArguments();
        $opts = $input->getOptions();

        [$sappId] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment']
        );

        $compose = $this->getComposeClient('wordpress');
        $compose->run($args['run']);
    }
}
