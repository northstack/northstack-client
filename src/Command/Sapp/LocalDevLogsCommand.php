<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Docker\Action\LogAction;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class LocalDevLogsCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'View logs from the local dev environment';

    protected function commandName(): string {
        return 'app:localdev:logs';
    }

    protected function getDockerAction()
    {
        return LogAction::class;
    }

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
        parent::execute($input, $output);


        $follow = $input->getOption('follow') ? true : false;
        $tail = $input->getOption('tail') ?: 'all';
        $action = $this->getAction();

        $action
            ->setFollow($follow)
            ->setTail($tail)
            ->run()
        ;
    }
}
