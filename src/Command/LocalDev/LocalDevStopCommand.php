<?php


namespace NorthStack\NorthStackClient\Command\LocalDev;

use NorthStack\NorthStackClient\Docker\Action\StopAction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalDevStopCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'Stop the local dev environment';

    protected function commandName(): string {
        return 'app:localdev:stop';
    }

    protected function getDockerAction()
    {
        return StopAction::class;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $output->writeln("Stopping containers");
        /** @var StopAction $action */
        $action = $this->getAction();
        $action->run();
    }
}
