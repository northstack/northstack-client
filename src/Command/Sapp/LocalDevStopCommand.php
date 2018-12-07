<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Docker\Action\StopAction;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use NorthStack\NorthStackClient\Docker;
use NorthStack\NorthStackClient\Docker\Action;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        $action = $this->getAction();
        $action->run();
    }
}
