<?php


namespace NorthStack\NorthStackClient\Docker\Action;

use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;
use NorthStack\NorthStackClient\Docker\DockerStreamHandler;
use NorthStack\NorthStackClient\Docker\DockerActionException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Docker\API\Exception\ContainerInspectNotFoundException;

abstract class BaseAction
{
    const DOCKER_IMAGE = 'docker/compose';
    const DOCKER_IMAGE_TAG = '1.23.3';

    /**
     * @var DockerClient
     */
    protected $docker;
    protected $input;
    protected $output;

    private static $label = 'com.northstack.localdev.version';
    private static $actions = [
        'start' => StartAction::class,
        'stop' => StopAction::class,
        'build' => BuildAction::class,
    ];

    protected $stack;

    protected $watchOutput = true;
    protected $attachInput = false;
    protected $handleSignals = false;
    protected $stopContainerOnExit = false;
    protected $destroyContainerOnExit = false;
    protected $destroyExistingContainer = false;
    protected $stopExistingContainer = true;

    protected $env = [];
    protected $name;
    /**
     * @var array
     */
    protected $appData;
    /**
     * @var DockerStreamHandler|null
     */
    protected $containerStreamHandler;

    public function __construct(
        string $stack,
        DockerClient $docker,
        InputInterface $input,
        OutputInterface $output,
        array $env,
        array $appData
    )
    {
        $this->stack = $stack;
        $this->docker = $docker;
        $this->input = $input;
        $this->output = $output;
        $this->env = $env;
        $this->appData = $appData;
    }

    public function run()
    {
        try {
            $this->createContainer();
            $this->containerStreamHandler = $this->attachOutput();
            $this->startContainer();
            $this->followOutput($this->containerStreamHandler);
            $this->finish();
        } catch (\Exception $e) {
            throw DockerActionException::fromException($e);
        }
    }

    protected function attachOutput()
    {
        if ($this->watchOutput) {
            $stream = $this->docker->attachOutput($this->getContainerName(), $this->attachInput);

            return new DockerStreamHandler(
                $stream,
                $this->output,
                $this->handleSignals
            );
        }

        return null;
    }

    protected function followOutput($stream)
    {
        /** @var DockerStreamHandler $stream */
        if ($this->watchOutput && $stream) {
            $ret = $stream->watch();
            if ($ret === $stream::$signaled) {
                $this->output->writeln('');
                $this->cleanup();
            }
        }
    }

    protected function cleanup()
    {
        $name = $this->getContainerName();
        $this->docker->signal($name, 'SIGINT');
    }

    abstract protected function getCmd(): array;

    protected function getImage()
    {
        return $this::DOCKER_IMAGE.':'.$this::DOCKER_IMAGE_TAG;
    }

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

    protected function getRoot()
    {
        return getenv('NS_LIB') ?: dirname(__DIR__, 3);
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

    protected function createContainer()
    {
        if (!$this->needsNewContainer()) {
            return;
        }

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
        return new \ArrayObject(['com.northstack.localdev' => '1']);
    }

    /**
     * @param $name
     * @return BaseAction
     * @throws \Exception
     */
    protected function getAction($name)
    {
        if (!array_key_exists($name, self::$actions)) {
            throw new \Exception("Unknown action: {$name}");
        }

        $action = self::$actions[$name];
        return new $action(
            $this->stack,
            $this->docker,
            $this->input,
            $this->output,
            $this->env
        );
    }
}
