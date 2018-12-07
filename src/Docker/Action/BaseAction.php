<?php


namespace NorthStack\NorthStackClient\Docker\Action;

use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;
use NorthStack\NorthStackClient\Docker\DockerStreamHandler;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected $stack;

    protected $watchOutput = true;
    protected $handleSignals = true;
    protected $stopContainerOnExit = true;
    protected $destroyContainerOnExit = true;
    protected $destroyExistingContainer = true;
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
        $this->createContainer();
        $this->startContainer();
        $this->finish();
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
        ;
        return $conf;
    }

    protected function getEnv()
    {
        return array_merge(
            [
                'COMPOSE_ROOT=/northstack/docker',
                'COMPOSE_ROOT_HOST='. getenv('NS_LIB') . '/docker',
            ],
            $this->env
        );
    }

    protected function getMounts()
    {
        return [
            [
                'src' => getenv('NS_LIB'),
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
        $this->docker->createContainer(
            $this->getContainerName(),
            $this->getContainerConfig(),
            $this->destroyExistingContainer,
            $this->stopExistingContainer
        );
    }

    protected function startContainer()
    {
        $name = $this->getContainerName();

        if ($this->watchOutput) {
            $this->handleIO($this->docker->run($name));
        } else {
            $this->docker->runDetached($name);
        }
    }

    protected function handleIO(AttachWebsocketStream $stream)
    {
        $handler = new DockerStreamHandler(
            $stream,
            $this->output,
            true
        );

        $ret = $handler->watch();
        if ($this->handleSignals && $ret === DockerStreamHandler::$signaled)
        {
            $this->output->writeln('');
            $this->cleanup();
        }
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
