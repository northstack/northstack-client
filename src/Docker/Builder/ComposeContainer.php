<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;

class ComposeContainer extends ContainerHelper
{
    const DOCKER_IMAGE = 'docker/compose';
    const DOCKER_IMAGE_TAG = '1.23.3';

    protected $baseLabel = 'com.northstack.localdev.compose';

    /**
     * @var string
     */
    protected $stack;
    /**
     * @var string
     */
    protected $name;

    public function __construct(string $stack, string $name, DockerClient $docker, $outputHandler = null)
    {
        parent::__construct("ns-compose-{$stack}", $docker, $outputHandler);
        $this->stack = $stack;
        $this->name = $name;
    }

    protected function getWorkingDir()
    {
        return '/northstack/docker';
    }

    protected function getMounts()
    {
        return [
            [
                'src' => $this->getRoot(),
                'dest' => '/northstack'
            ],
            [
                'src' => '/var/run/docker.sock',
                'dest' => '/var/run/docker.sock'
            ]
        ];
    }

    protected function getContainerName()
    {
        return "ns-compose-{$this->stack}-{$this->name}";
    }

    protected function getImage()
    {
        return self::DOCKER_IMAGE.':'.self::DOCKER_IMAGE_TAG;
    }

    protected function getContainerConfig()
    {
        $conf = new Container();
        $conf
            ->setBindMounts($this->getMounts())
            ->setImage($this->getImage())
            ->setCmd($this->getCmd())
            ->setEnv($this->getEnv())
            ->setWorkingDir($this->getWorkingDir())
            ->setAttachStdout($this->watchOutput)
            ->setAttachStderr($this->watchOutput)
            ->setLabels($this->getLabels())
        ;
        return $conf;
    }

    protected function getEnv()
    {
        $lib = $this->getRoot();
        return array_merge(
            [
                'COMPOSE_ROOT=/northstack/docker',
                "COMPOSE_ROOT_HOST={$lib}/docker",
                "COMPOSE_FILE=docker-compose.yml:docker-compose-{$this->stack}.yml",
            ],
            $this->env
        );
    }
}
