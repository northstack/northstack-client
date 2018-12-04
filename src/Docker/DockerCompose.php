<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\Docker;


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
            $this->buildConfig(),
            true
        );
    }

    public function buildConfig()
    {
        return [];
    }

    public function watch()
    {
    }

    public function stop()
    {
    }
}
