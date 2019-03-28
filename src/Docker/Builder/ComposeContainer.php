<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;

class ComposeContainer extends ContainerHelper
{
    const DOCKER_IMAGE = 'docker/compose';
    const DOCKER_IMAGE_TAG = '1.23.2';

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
        return getenv('HOME').'/.local/northstack/docker';
    }

    protected function getMounts()
    {
        $HOME = getenv('HOME');

        return [
            [
                'src' => "${HOME}/.local/northstack",
                'dest' => "${HOME}/.local/northstack",
            ],
            [
                'src' => $this->getRoot(),
                'dest' => '/northstack'
            ],
            [
                'src' => $this->getRoot(),
                'dest' => $this->getRoot(),
            ],
            [
                'src' => $this->getRoot().'/app',
                'dest' => $this->getRoot().'/app',
            ],
            [
                'src' => $this->getRoot().'/public',
                'dest' => $this->getRoot().'/public',
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
        $HOME = getenv('HOME');

        /** @noinspection DegradedSwitchInspection */
        switch ($this->stack) {
            case 'static':
                $composeFiles = "docker-compose.yml";
                break;
            case 'wordpress':
                $composeFiles = "docker-compose.yml:docker-compose-{$this->stack}.yml";
                break;
            default:
                $composeFiles = "docker-compose-{$this->stack}.yml";
                break;
        }

        return array_merge(
            [
                "COMPOSE_ROOT_HOST=${HOME}/.local/northstack/docker",
                "COMPOSE_ROOT=${HOME}/.local/northstack/docker",
                "COMPOSE_FILE=${composeFiles}",
            ],
            $this->env
        );
    }
}
