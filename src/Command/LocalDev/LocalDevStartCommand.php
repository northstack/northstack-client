<?php


namespace NorthStack\NorthStackClient\Command\LocalDev;

use NorthStack\NorthStackClient\Docker\Action\StartAction;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalDevStartCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'Start the local dev environment';

    protected function commandName(): string {
        return 'app:localdev:start';
    }

    protected function getDockerAction()
    {
        return StartAction::class;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->addOption('detach', 'd', InputOption::VALUE_NONE)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        /** @var StartAction $action */
        $action = $this->getAction();
        $background = $input->getOption('detach') ?: false;

        $output->writeln("Starting up...");
        $action->setBackground($background);
        $action->run();
    }
}
