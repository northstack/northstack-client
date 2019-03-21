<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use NorthStack\NorthStackClient\Build\ScriptConfig;
use NorthStack\NorthStackClient\Docker\DockerClient;

abstract class AbstractBuilder
{
    /**
     * @var ScriptConfig
     */
    protected $config;
    /**
     * @var DockerClient
     */
    protected $dockerClient;
    /**
     * @var string
     */
    protected $containerId;
    protected $baseFolder;

    public function __construct(
        ScriptConfig $config,
        DockerClient $client,
        string $containerId,
        string $baseFolder = ''
    ) {
        $this->config = $config;
        $this->dockerClient = $client;
        $this->containerId = $containerId;
        $this->baseFolder = $baseFolder;
    }

    protected function exec(string $command, array $env = [])
    {
        $cmd = ['bash', '-c', 'source /root/.bashrc && '.$command];

        $this->dockerClient->exec($this->containerId, $cmd, $env);
    }
}
