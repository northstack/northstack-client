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
    protected $nsLib;

    public function __construct(string $stack, string $name, DockerClient $docker, $outputHandler = null)
    {
        parent::__construct("ns-compose-{$stack}", $docker, $outputHandler);
        $this->stack = $stack;
        $this->name = $name;
    }

    protected function getWorkingDir()
    {
        return $this->getNsLib() . '/docker';
    }

    protected function getMounts()
    {
        $nsLib = $this->getNsLib();

        return [
            [
                'src' => $nsLib,
                'dest' => $nsLib,
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
        $stack = strtolower($this->stack);
        // check NS_LIB env variable
        $nsLib = $this->getNsLib();

        /** @noinspection DegradedSwitchInspection */
        switch ($stack) {
            case 'static':
                $composeFiles = "{$nsLib}/docker/docker-compose.yml";
                break;
            case 'wordpress':
                $composeFiles = "{$nsLib}/docker/docker-compose.yml:{$nsLib}/docker/docker-compose-{$stack}.yml";
                break;
            default:
                $composeFiles = "{$nsLib}/docker/docker-compose-{$stack}.yml";
                break;
        }

        return array_merge(
            [
                "COMPOSE_ROOT_HOST=${nsLib}/docker",
                "COMPOSE_ROOT=${nsLib}/docker",
                "COMPOSE_FILE=${composeFiles}",
            ],
            $this->env
        );
    }

    protected function getNsLib()
    {
        if (!$this->nsLib) {
            // check NS_LIB env variable, if not set, this is based on the actual parent of the file currently being run
            $this->nsLib = getenv('NS_LIB');
            if (!$this->nsLib) {
                $this->nsLib = dirname(__FILE__, 4);
            }
        }


        return $this->nsLib;
    }
}
