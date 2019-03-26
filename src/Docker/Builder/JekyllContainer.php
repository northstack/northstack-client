<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use NorthStack\NorthStackClient\Docker\DockerClient;

class JekyllContainer extends ContainerHelper
{
    const DOCKER_IMAGE = 'jekyll/jekyll';
    /**
     * @var string
     */
    protected $jekyllVersion;
    protected $cmd = ['jekyll', 'build'];
    protected $baseLabel = 'com.northstack.localdev.jekyll';
    /**
     * @TODO currently there's something weird with docker-php or the docker api
     * Where it doesn't return a packed header, and instead reads the first line
     * "ruby 2.8" which causes it to say 540+MB of data is being returned
     * which blows out memory since the library tries to force-read that
     * amount of data :(
     */
    // protected $watchOutput = true;
    /**
     * @TODO Unfortunately watching websocket is also not working properly
     * We will just have to live without container output for now
     */
    // protected $watchWebsocket = true;

    /**
     * JekyllContainer constructor.
     * @param string $containerName
     * @param DockerClient $docker
     * @param \Closure $outputHandler
     * @param string $jekyllVersion
     */
    public function __construct(string $containerName, DockerClient $docker, $outputHandler = null, $jekyllVersion = '3')
    {
        parent::__construct($containerName, $docker, $outputHandler);
        $this->jekyllVersion = $jekyllVersion;
    }

    protected function getImage()
    {
        return self::DOCKER_IMAGE.':'.$this->jekyllVersion;
    }
}
