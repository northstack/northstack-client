<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


abstract class AbstractLocalDevCmd extends Command
{
    protected $skipLoginCheck = true;
    protected $commandDescription;

    use SappEnvironmentTrait;

    public function __construct()
    {
        parent::__construct($this->commandName());
    }

    protected abstract function commandName(): string;

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription($this->commandDescription)
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
        ;
    }

    protected function buildComposeOptions()
    {
        return [];
    }

    protected function getComposeClient($stack)
    {
        $docker = new Docker\DockerClient();
        $compose = new Docker\DockerCompose($docker, $stack, $this->buildComposeOptions());
        return $compose;
    }
}
