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

    public function start()
    {
        $this->client->run(
            $this->getName(),
            $this->image,
            $this->buildConfig(['Cmd' => ['up', '-d']]),
            false
        );
    }

    protected function buildConfig($opts)
    {
        $config = [
            'Env' => $this->buildEnv(),
            'HostConfig' => $this->buildHostConfig(),
            'WorkingDir' => "/northstack/docker/{$this->stack}",
        ];

        foreach ($opts as $k => $v) {
            $config[$k] = $v;
        }

        return $config;
    }

    protected function buildEnv()
    {
        return [
        ];
    }

    protected function buildHostConfig()
    {
        $config = new HostConfig();
        $lib = getenv('NS_LIB');
        return $config->setBinds([
            "{$lib}:/northstack",
            '/var/run/docker.sock:/var/run/docker.sock'
        ]);
    }

    public function watch()
    {
    }

    public function stop()
    {
        $this->client->run(
            $this->getName(),
            $this->image,
            $this->buildConfig(['Cmd' => ['down']]),
            false
        );
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
            $this->image,
            $this->buildConfig(['Cmd' => $cmd]),
            true
        );
    }
}
