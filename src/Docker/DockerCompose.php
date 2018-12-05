<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\Docker;
use Docker\API\Model\Mount;
use Docker\API\Model\HostConfig;

class DockerCompose
{
    /**
     * @var DockerClient
     */
    private $client;

    protected $stack;
    protected $env;

    protected $image = 'docker/compose:1.23.2';

    public function __construct(
        DockerClient $docker,
        String $stack,
        Array $env
    )
    {
        $this->client = $docker;
        $this->stack = $stack;
        $this->env = $env;
    }

    public function getName()
    {
        return "ns-compose-{$this->stack}";
    }

    public function start($background = true)
    {
        $cmd = ['up'];
        if ($background) {
            $cmd[] = '-d';
        }
        $this->run($cmd);
    }

    public function run($cmd)
    {
        $this->client->run(
            $this->getName(),
            $this->buildConfig(['Cmd' => $cmd]),
            false
        );
    }

    protected function buildConfig($opts)
    {
        $container = new Container();
        return $container
            ->setImage($this->image)
            ->setEnv($this->getEnv())
            ->setWorkingDir("/northstack/docker/{$this->stack}")
            ->setBindMounts($this->getMounts())
            ->update($opts)
        ;
    }

    protected function getEnv()
    {
        return [
            'COMPOSE_ROOT=/northstack/docker',
            'COMPOSE_ROOT_HOST='. getenv('NS_LIB') . '/docker',
        ];
    }

    protected function getMounts()
    {
        return [
            [
                'src' => getenv('NS_LIB'),
                'dest' => '/northstack'
            ],
            [
                'src' => '/var/run/docker.sock',
                'dest' => '/var/run/docker.sock'
            ]
        ];
    }

    public function stop()
    {
        $this->run(['down']);
        $this->client->stop($this->getName());
    }

    public function logs($follow = true, $tail = 'all')
    {
        $cmd = ['logs', "--tail={$tail}"];
        if ($follow) {
            $cmd[] = '--follow';
        }
        $this->client->run(
            $this->getName(),
            $this->buildConfig(['Cmd' => $cmd]),
            true
        );
    }
}
