<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\Command\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class LocalDevLogsCommand extends AbstractLocalDevCmd
{
    protected $commandName = 'app:localdev:logs';
    protected $commandDescription = 'View logs from the local dev environment';


    public function configure()
    {
        parent::configure();
        $this
            ->addOption('follow', 'f', InputOption::VALUE_NONE)
            ->addOption('tail', 't', InputOption::VALUE_REQUIRED, 'View the last N lines of the logs only')
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

        if (isset($opts['tail'])) {
            $compose->logs(
                $opts['follow'],
                $opts['tail']
            );
        } else {
            $compose->logs($opts['follow']);
        }

    }
}
