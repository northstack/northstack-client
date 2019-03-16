<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use NorthStack\NorthStackClient\Build\ScriptConfig;
use NorthStack\NorthStackClient\Docker\Container;
use NorthStack\NorthStackClient\Enumeration\BuildScriptType;
use RuntimeException;
use Throwable;

class BuildScriptHelper extends ContainerHelper
{
    const DOCKER_IMAGE = 'northstack/docker-builder';
    const DOCKER_IMAGE_TAG = 'latest';

    const BUILDERS = [
        'php' => PHPBuilder::class,
        'node' => NodeBuilder::class,
        'bash' => BashBuilder::class,
        'ruby' => RubyBuilder::class,
        'python' => PythonBuilder::class,
    ];

    protected function getImage()
    {
        return self::DOCKER_IMAGE.':'.self::DOCKER_IMAGE_TAG;
    }

    public function runScripts(string $appType, \stdClass $buildConfig)
    {
        foreach ($buildConfig->{'build-scripts'} as $script) {
            try {
                $type = BuildScriptType::memberByValue($script->type);

                $config = ScriptConfig::fromConfig($type, $script);

                $builderClass = self::BUILDERS[$type->value()];
                /** @var BuilderInterface $builder */
                $builder = new $builderClass($config, $this->docker, $this->getContainerName());

                $start = time();
                $builder->run();
                $total = time() - $start;

                $this->{'outputHandler'}($config->getScript().' took '.$total.' seconds');
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
        }

        switch ($appType) {
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
                    ->setImage('jekyll/jekyll:'.$buildConfig->{'framework-version'})
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
}
