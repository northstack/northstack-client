<?php


namespace NorthStack\NorthStackClient\Docker\Action;


use NorthStack\NorthStackClient\Docker\DockerClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildAction extends BaseAction
{
    const DOCKER_IMAGE = 'northstack/docker-builder';
    const DOCKER_IMAGE_TAG = 'latest';

    public function __construct(
        string $stack,
        DockerClient $docker,
        InputInterface $input,
        OutputInterface $output,
        array $env,
        array $appData
    ) {
        $this->name = 'build';
        $this->attachInput = true;
        $this->stopContainerOnExit = true;
        $this->watchOutput = false;
        $this->destroyContainerOnExit = true;
        parent::__construct($stack, $docker, $input, $output, $env, $appData);
    }

    protected function getRoot()
    {
        return getenv('NS_PWD') ?: getcwd();
    }

    protected function getMounts()
    {
        return [
            [
                'src' => $this->getRoot(),
                'dest' => '/app'
            ],
        ];
    }

    protected function getWorkingDir()
    {
        return '/app';
    }

    protected function getContainerConfig()
    {
        $conf = parent::getContainerConfig();

        return $conf
            ->setTty(true)
            ->setOpenStdin(true)
            ->setStdinOnce(false);
    }

    protected function startContainer()
    {
        $this->docker->run($this->getContainerName());
        $this->docker->exec($this->getContainerName(), ['echo', 'hello']);

    }

    public function build($path, array $scripts, OutputInterface $output)
    {
        if (!empty($scripts)) {
            $this->docker->pullImage(self::DOCKER_IMAGE.':latest');
        }
    }

    protected function getCmd(): array
    {
        return ['/bin/bash'];
    }
}
