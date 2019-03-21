<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use Docker\API\Exception\ContainerInspectNotFoundException;
use Docker\Stream\AttachWebsocketStream;
use Docker\Stream\DockerRawStream;
use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;
use NorthStack\NorthStackClient\Docker\DockerStreamHandler;
use Symfony\Component\Console\Output\OutputInterface;
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
    protected $watchWebsocket = false;

    protected $stopContainerOnExit = false;
    protected $destroyContainerOnExit = false;
    protected $destroyExistingContainer = false;
    protected $stopExistingContainer = true;
    protected $baseLabel = 'com.northstack.localdev';
    static $label = 'com.northstack.localdev.version';

    /**
     * @var \Closure
     */
    protected $outputHandler;
    /**
     * @var array
     */
    protected $cmd = [];
    /**
     * @var string
     */
    protected $containerName;
    protected $appRoot;
    /**
     * @var \Psr\Http\Message\ResponseInterface|AttachWebsocketStream
     */
    protected $websocket;

    public function __construct(string $containerName, DockerClient $docker, $outputHandler = null)
    {
        $this->docker = $docker;
        $this->outputHandler = $outputHandler ?: function () {};
        $this->containerName = $containerName;
    }

    public function setRoot($appRoot)
    {
        $this->appRoot = $appRoot;
    }

    protected function log($msg)
    {
        $handler = $this->outputHandler;

        $handler($msg);
    }

    public function pushArchive(string $file, string $containerId = null, string $dest = '/')
    {
        $this->docker->pushArchive($file, $containerId ?: $this->getContainerName(), $dest);
    }

    public function attachOutput(OutputInterface $output, $attachInput = false, $handleSignals = false)
    {
        if ($this->watchOutput) {
            $stream = $this->docker->attachOutput($this->getContainerName(), $attachInput);

            return new DockerStreamHandler(
                $stream,
                $output,
                $handleSignals
            );
        }

        if ($this->watchWebsocket) {
            return $this->docker->attachWebsocket($this->getContainerName());
        }

        return null;
    }

    public function followOutput($stream, OutputInterface $output)
    {
        /** @var DockerStreamHandler $stream */
        if ($this->watchOutput && $stream instanceof DockerRawStream) {
            $ret = $stream->watch();
            if ($ret === $stream::$signaled) {
                $output->writeln('');
                $this->cleanup();
            }
        }
        if ($this->watchWebsocket && $stream instanceof AttachWebsocketStream) {
            while (($data = $stream->read()) !== null) {
                if ($data) {
                    $output->writeln($data);
                }
            }
        }
    }

    protected function cleanup()
    {
        $name = $this->getContainerName();
        $this->docker->signal($name, 'SIGINT');
    }

    public function finish()
    {
        if (!$this->stopContainerOnExit) {
            return;
        }

        $this->docker->stop(
            $this->getContainerName(),
            $this->destroyContainerOnExit
        );
    }

    protected function getRoot()
    {
        return $this->appRoot;
    }

    protected function getEnv()
    {
        return [];
    }

    protected function getMounts()
    {
        $mounts = [
            [
                'src' => $this->getRoot().'/app',
                'dest' => '/app'
            ],
            [
                'src' => $this->getRoot().'/config',
                'dest' => '/config'
            ],
            [
                'src' => $this->getRoot().'/scripts',
                'dest' => '/scripts'
            ],
        ];

        return $mounts;
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
        return $this->containerName;
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

        if (!array_key_exists($this->baseLabel, $labels))
        {
            return true;
        }

        $old = $labels[$this->baseLabel];
        return $old !== $new;
    }

    protected function getLabels()
    {
        return new \ArrayObject([$this->baseLabel => '1']);
    }

    public function createContainer()
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

    public function startContainer()
    {
        $this->docker->run($this->getContainerName());
    }

    protected function getCmd(): array
    {
        return $this->cmd;
    }

    public function setCmd($cmd = [])
    {
        $this->cmd = $cmd;
    }
}
