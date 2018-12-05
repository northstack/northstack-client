<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\Command\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LocalDevStartCommand extends AbstractLocalDevCmd
{
    protected $commandDescription = 'Start the local dev environment';

    protected function commandName(): string {
        return 'app:localdev:start';
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $input->getArguments();

        [$sappId] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment']
        );

        $this->getComposeClient('wordpress')->start();

    }
}
