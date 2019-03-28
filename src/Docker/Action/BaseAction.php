<?php


namespace NorthStack\NorthStackClient\Docker\Action;

use NorthStack\NorthStackClient\Docker\Builder\BuildScriptContainer;
use NorthStack\NorthStackClient\Docker\Builder\ComposeContainer;
use NorthStack\NorthStackClient\Docker\Builder\ContainerHelper;
use NorthStack\NorthStackClient\Docker\Builder\JekyllContainer;
use NorthStack\NorthStackClient\Docker\DockerClient;
use NorthStack\NorthStackClient\Docker\DockerStreamHandler;
use NorthStack\NorthStackClient\Docker\DockerActionException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseAction
{
    /**
     * @var DockerClient
     */
    protected $docker;
    /**
     * @var InputInterface
     */
    protected $input;
    /**
     * @var OutputInterface
     */
    protected $output;

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
    /**
     * @var ComposeContainer|ContainerHelper|BuildScriptContainer|JekyllContainer
     */
    protected $container;
    /**
     * @var string
     */
    protected $localAppFolder;

    public function __construct(
        string $stack,
        DockerClient $docker,
        InputInterface $input,
        OutputInterface $output,
        array $env,
        array $appData,
        $localAppFolder
    )
    {
        $this->stack = $stack;
        $this->docker = $docker;
        $this->input = $input;
        $this->output = $output;
        $this->env = $env;
        $this->appData = $appData;
        $this->container = new ComposeContainer(
            $stack,
            $this->name,
            $docker
        );
        $this->localAppFolder = $localAppFolder;
    }

    /** @noinspection PhpMethodMayBeStaticInspection */
    protected function prepare()
    {
    }

    /** @noinspection PhpMethodMayBeStaticInspection */
    protected function continue()
    {
    }

    public function run()
    {
        try {
            $this->container->setRoot($this->localAppFolder);
            $this->prepare();
            $this->container->setEnv($this->env);
            $this->container->createContainer();
            $this->containerStreamHandler = $this->container->attachOutput($this->output, $this->attachInput, $this->handleSignals);
            $this->container->startContainer();
            $this->container->followOutput($this->containerStreamHandler, $this->output);
            $this->continue();
            $this->container->finish();
        } catch (\Exception $e) {
            throw DockerActionException::fromException($e);
        }
    }

    /**
     * @param $name
     * @return StartAction|StopAction|BuildAction
     * @throws \Exception
     */
    protected function createAction($name)
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
