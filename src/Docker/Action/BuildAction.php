<?php


namespace NorthStack\NorthStackClient\Docker\Action;


use NorthStack\NorthStackClient\Docker\Builder;
use NorthStack\NorthStackClient\Docker\DockerClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildAction extends BaseAction
{
    public function __construct(
        string $stack,
        DockerClient $docker,
        InputInterface $input,
        OutputInterface $output,
        array $env,
        array $appData,
        string $appFolder
    ) {
        $this->name = 'build';
        $this->attachInput = true;
        $this->stopContainerOnExit = true;
        $this->watchOutput = false;
        $this->destroyContainerOnExit = true;
        parent::__construct($stack, $docker, $input, $output, $env, $appData, $appFolder);

        $this->container = new Builder\BuildScriptContainer(
            "ns-{$stack}-builder",
            $docker
        );
    }

    protected function continue()
    {
        $this->container->runScripts($this->appData['build']);
    }

    public function run()
    {
        parent::run();

        // run build-scripts
        $buildConfig = $this->appData['build'];

        switch (strtoupper($this->appData['config']->app_type)) {
            case 'GATSBY':
                $this->container = new Builder\BuildScriptContainer('ns-gatsby-builder', $this->docker, null);
                $this->container
                    ->setRoot($this->localAppFolder)
                    ->setEntryPoint(['gatsby.sh'])
                    ->setEnv(['GATSBY_VERSION' => $buildConfig->framework_version])
                    ->createContainer();

                $this->containerStreamHandler = $this->container->attachOutput($this->output, $this->attachInput, $this->handleSignals);
                $this->container->startContainer();
                $this->container->followOutput($this->containerStreamHandler, $this->output);
                $this->container->finish();
                break;
            case 'JEKYLL':
                $this->container = new Builder\JekyllContainer('ns-jekyll-builder' ,$this->docker, null, $buildConfig->framework_version);

                $this->container
                    ->setRoot($this->localAppFolder)
                    ->setEnv(['JEKYLL_DATA_DIR=/app'])
                    ->createContainer();

                $this->containerStreamHandler = $this->container->attachOutput($this->output, $this->attachInput, $this->handleSignals);
                $this->container->startContainer();
                $this->container->followOutput($this->containerStreamHandler, $this->output);
                $this->container->finish();
                break;
            case 'STATIC':
            case 'WORDPRESS':
            default:
                break;
        }
    }
}
