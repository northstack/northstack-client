<?php


namespace NorthStack\NorthStackClient\Command\LocalDev;


use NorthStack\NorthStackClient\Docker\Action\BuildAction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LocalDevBuildCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'Run build scripts (do this before start)';

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription($this->commandDescription)
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Environment', 'local')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $this->action->run();
    }

    protected function commandName(): string
    {
        return 'app:localdev:build';
    }

    protected function getDockerAction()
    {
        return BuildAction::class;
    }
}
