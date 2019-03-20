<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


use NorthStack\NorthStackClient\Build\ScriptConfig;
use NorthStack\NorthStackClient\Enumeration\BuildScriptType;
use RuntimeException;
use Throwable;

class BuildScriptContainer extends ContainerHelper
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

    protected $baseLabel = 'com.northstack.localdev.buildscripts';

    protected function getImage()
    {
        return self::DOCKER_IMAGE.':'.self::DOCKER_IMAGE_TAG;
    }

    public function runScripts(\stdClass $buildConfig)
    {
        $outputHandler = $this->outputHandler;
        if (isset($buildConfig->{'build-scripts'})) {
            foreach ($buildConfig->{'build-scripts'} as $script) {
                try {
                    $type = BuildScriptType::memberByValue($script->type);
                    $outputHandler('Running build-script type: '.$type->value());

                    $config = ScriptConfig::fromConfig($type, $script);

                    $builderClass = self::BUILDERS[$type->value()];
                    /** @var BuilderInterface $builder */
                    $builder = new $builderClass($config, $this->docker, $this->getContainerName());

                    $start = time();
                    $builder->run();
                    $total = time() - $start;

                    $outputHandler($config->getScript().' took '.$total.' seconds');
                } catch (Throwable $e) {
                    throw new RuntimeException($e->getMessage(), 0, $e);
                }
            }
        }
    }
}
