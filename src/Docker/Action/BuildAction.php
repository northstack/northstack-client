<?php


namespace NorthStack\NorthStackClient\Docker\Action;


use NorthStack\NorthStackClient\Build\ScriptConfig;
use NorthStack\NorthStackClient\Docker\Builder;
use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Docker\DockerClient;
use NorthStack\NorthStackClient\Enumeration\BuildScriptType;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class BuildAction extends BaseAction
{
    const DOCKER_IMAGE = 'northstack/docker-builder';
    const DOCKER_IMAGE_TAG = 'latest';

    const BUILDERS = [
        'php' => Builder\PHPBuilder::class,
        'node' => Builder\NodeBuilder::class,
        'bash' => Builder\BashBuilder::class,
        'ruby' => Builder\RubyBuilder::class,
        'python' => Builder\PythonBuilder::class,
    ];

    public function __construct(
        string $stack,
        DockerClient $docker,
        InputInterface $input,
        OutputInterface $output,
        array $env,
        array $appData
    ) {
        $this->name = 'build';
        $this->attachInput = true;
        $this->stopContainerOnExit = true;
        $this->watchOutput = false;
        $this->destroyContainerOnExit = true;
        parent::__construct($stack, $docker, $input, $output, $env, $appData);
    }

    protected function getRoot()
    {
        return getenv('NS_PWD') ?: getcwd();
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

    protected function getContainerConfig()
    {
        $conf = parent::getContainerConfig();

        return $conf
            ->setShell(['/bin/bash'])
            ->setTty(true)
            ->setOpenStdin(true)
            ->setStdinOnce(false);
    }

    protected function createContainer()
    {
        try {
            $this->docker->deleteContainer($this->getContainerName(), true);
        } catch (Throwable $e) {}

        parent::createContainer();
    }

    protected function startContainer()
    {
        $this->docker->run($this->getContainerName());

        foreach ($this->appData['build']->{'build-scripts'} as $script) {
            try {
                $type = BuildScriptType::memberByValue($script->type);

                $config = ScriptConfig::fromConfig($type, $script);

                $builderClass = self::BUILDERS[$type->value()];
                /** @var Builder\BuilderInterface $builder */
                $builder = new $builderClass($config, $this->docker, $this->getContainerName());

                $start = time();
                $builder->run();
                $total = time() - $start;

                $this->output->writeln($config->getScript().' took '.$total.' seconds');
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
        }

        switch ($this->appData['config']->{'app-type'}) {
            case 'static':
                break;
            case 'wordpress':
                break;
            case 'jekyll':
                $containerName = $this->getContainerName().'-jekyll';

                try {
                    $this->docker->deleteContainer($containerName);
                } catch (\Exception $e) {}

                $mounts = ['src' => $this->getRoot(), 'dest' => '/srv/jekyll'];
                /** @var Container $containerConfig */
                $containerConfig = (new Container())
                    ->setBindMounts([
                        $mounts,
                    ])
                    ->setImage('jekyll/jekyll:'.$this->appData['build']->{'framework-version'})
                    ->setCmd(['jekyll', 'build'])
                    ->setAttachStdout($this->watchOutput)
                    ->setAttachStderr($this->watchOutput)
                    ->setLabels($this->getLabels())
                    ;

                $this->docker->createContainer(
                    $containerName,
                    $containerConfig
                );
                $this->docker->run($containerName);

                try {
                    $this->docker->deleteContainer($containerName);
                } catch (\Exception $e) {}
                break;
        }
    }

    protected function getCmd(): array
    {
        return [];
    }
}
