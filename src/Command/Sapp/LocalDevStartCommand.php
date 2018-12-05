<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\Command\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalDevStartCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'Start the local dev environment';

    protected function commandName(): string {
        return 'app:localdev:start';
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

        $background = $input->getOption('detach') ?: false;
        $this->getComposeClient('wordpress')->start($background);

    }
}
