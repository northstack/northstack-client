<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use Docker\API\Exception\ContainerInspectNotFoundException;
use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;
use Throwable;

class ContainerHelper
{
    const DOCKER_IMAGE = 'docker/compose';
    const DOCKER_IMAGE_TAG = '1.23.3';

    /**
     * @var DockerClient
     */
    protected $docker;
    protected $watchOutput = false;

    protected $stopContainerOnExit = false;
    protected $destroyContainerOnExit = false;
    protected $destroyExistingContainer = false;
    protected $stopExistingContainer = true;
    static $label = 'com.northstack.localdev.version';

    /**
     * @var \Closure
     */
    protected $outputHandler;

    public function __construct(DockerClient $docker, $outputHandler = null)
    {
        $this->docker = $docker;
        $this->outputHandler = $outputHandler ?: function () {};
    }

    protected function getRoot()
    {
        return getenv('NS_PWD') ?: getcwd();
    }

    protected function getEnv()
    {
        return [];
    }

    protected function getMounts()
    {
        return [
            [
                'src' => $this->getRoot().'/app',
                'dest' => '/app'
            ],
            [
                'src' => $this->getRoot().'/scripts',
                'dest' => '/scripts'
            ],
        ];
    }

    protected function getWorkingDir()
    {
        return '/app';
    }

    protected function getImage()
    {
        return self::DOCKER_IMAGE.':'.self::DOCKER_IMAGE_TAG;
    }

    /**
     * @return \Docker\API\Model\ContainersCreatePostBody|Container
     */
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
        ;

        return $conf
            ->setShell(['/bin/bash'])
            ->setTty(true)
            ->setOpenStdin(true)
            ->setStdinOnce(false);
    }

    protected function getContainerName()
    {
        return "ns-compose-build-scripts";
    }

    protected function getContainerVersion()
    {
        $data = Container::allValues($this->getContainerConfig());
        return sha1(json_encode($data));
    }

    protected function needsNewContainer()
    {
        if ($this->destroyExistingContainer)
        {
            return true;
        }

        $new = $this->getContainerVersion();

        try {
            $labels = $this->docker->getLabels($this->getContainerName());
        } catch (ContainerInspectNotFoundException $e) {
            return true;
        }

        if (!array_key_exists(self::$label, $labels))
        {
            return true;
        }

        $old = $labels[self::$label];
        return $old !== $new;
    }

    protected function getLabels()
    {
        return new \ArrayObject(['com.northstack.localdev' => '1']);
    }

    protected function createContainer()
    {
        if (!$this->needsNewContainer()) {
            return;
        }

        try {
            $this->docker->deleteContainer($this->getContainerName(), true);
        } catch (Throwable $e) {}

        $this->docker->pullImage($this->getImage());

        $conf = $this->getContainerConfig();
        $labels = $this->getLabels();
        $labels->offsetSet(
            self::$label,
            $this->getContainerVersion()
        );
        $conf->setLabels($labels);

        $this->docker->createContainer(
            $this->getContainerName(),
            $conf,
            true,
            $this->stopExistingContainer
        );
    }

    protected function startContainer()
    {
        $this->docker->run($this->getContainerName());
    }

    protected function getCmd(): array
    {
        return [];
    }
}
