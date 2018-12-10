<?php


namespace NorthStack\NorthStackClient\Docker\Action;

use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;
use NorthStack\NorthStackClient\Docker\DockerStreamHandler;
use NorthStack\NorthStackClient\Docker\DockerActionException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Docker\API\Exception\ContainerInspectNotFoundException;
use Docker\API\Model\ErrorResponse;
use Docker\Stream\AttachWebsocketStream;

abstract class BaseAction
{

    /**
     * @var DockerClient
     */
    protected $docker;
    protected $input;
    protected $output;

    private static $image = 'docker/compose:1.23.2';
    private static $label = 'com.northstack.localdev.version';

    protected $stack;

    protected $watchOutput = true;
    protected $handleSignals = false;
    protected $stopContainerOnExit = false;
    protected $destroyContainerOnExit = false;
    protected $destroyExistingContainer = false;
    protected $stopExistingContainer = true;

    protected $env = [];
    protected $name;

    public function __construct(
        string $stack,
        DockerClient $docker,
        InputInterface $input,
        OutputInterface $output,
        Array $env
    )
    {
        $this->stack = $stack;
        $this->docker = $docker;
        $this->input = $input;
        $this->output = $output;
        $this->env = $env;
    }

    public function run()
    {
        try {
            $this->createContainer();
            $outputStream = $this->attachOutput();
            $this->startContainer();
            $this->followOutput($outputStream);
            $this->finish();
        } catch (\Exception $e) {
            throw DockerActionException::fromException($e);
        }
    }

    protected function attachOutput()
    {
        if (!$this->watchOutput) {
            return;
        }

        $stream = $this->docker->attachOutput($this->getContainerName());

        return new DockerStreamHandler(
            $stream,
            $this->output,
            $this->handleSignals
        );
    }

    protected function followOutput($stream)
    {
        if (!$this->watchOutput) {
            return;
        }
        $ret = $stream->watch();
        if ($ret === $stream::$signaled) {
            $this->output->writeln('');
            $this->cleanup();
        }
    }

    protected function cleanup()
    {
        $name = $this->getContainerName();
        $this->docker->signal($name, 'SIGINT');
    }

    abstract protected function getCmd(): Array;

    protected function finish()
    {
        if (!$this->stopContainerOnExit) {
            return;
        }

        $this->docker->stop(
            $this->getContainerName(),
            $this->destroyContainerOnExit
        );
    }

    protected function getContainerConfig()
    {
        $conf = new Container();
        $conf
            ->setImage(self::$image)
            ->setCmd($this->getCmd())
            ->setEnv($this->getEnv())
            ->setBindMounts($this->getMounts())
            ->setWorkingDir("/northstack/docker/{$this->stack}")
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
            ],
            $this->env
        );
    }

    protected function getRoot()
    {
        return getenv('NS_LIB') ?: dirname(__DIR__, 3);
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

    protected function createContainer()
    {
        if (!$this->needsNewContainer()) {
            return;
        }

        $conf = $this->getContainerConfig();
        $conf->setLabels($this->getLabels()->append($this->getVersionLabel()));

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

    protected function getContainerVersion()
    {
        $data = Container::allValues($this->getContainerConfig());
        return sha1(json_encode($data));
    }

    protected function getLabels()
    {
        return new \ArrayObject(['com.northstack.localdev' => 1]);
    }

    protected function getVersionLabel()
    {
        $version = $this->getContainerVersion();
        return new \ArrayObject([
            self::$label => $version
        ]);
    }

    protected function getAction($name)
    {
        $actions = [
            'start' => StartAction::class,
            'stop' => StopAction::class
        ];
        if (!array_key_exists($name, $actions)) {
            throw new \Exception("Unknown action: {$name}");
        }

        $action = $actions[$name];
        return new $action(
            $this->stack,
            $this->docker,
            $this->input,
            $this->output,
            $this->env
        );
    }
}
