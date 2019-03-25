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

    public function runScripts(\stdClass $buildConfig, array $waitForFiles = [])
    {
        if (isset($buildConfig->{'build-scripts'})) {
            $first = true;
            foreach ($buildConfig->{'build-scripts'} as $script) {
                try {
                    $type = BuildScriptType::memberByValue($script->type);
                    $this->log('Running build-script type: '.$type->value());

                    $config = ScriptConfig::fromConfig($type, $script);

                    $scriptLocation = $this->getRoot().'/scripts/'.$config->getScript();
                    if (!file_exists($scriptLocation)) {
                        $this->log('Script not found at '.$this->getRoot().'/scripts/'.$config->getScript());
                        $this->log("Contents of {$this->getRoot()}/scripts:");
                        foreach (scandir($this->getRoot() . '/scripts', SCANDIR_SORT_NONE) as $file) {
                            $this->log($file);
                        }
                        throw new RuntimeException('Script not found');
                    }
                    $builderClass = self::BUILDERS[$type->value()];
                    /** @var BuilderInterface|AbstractBuilder $builder */
                    $builder = new $builderClass($config, $this->docker, $this->getContainerName());

                    if ($first) {
                        $this->log('Contents of /scripts:');
                        $builder->ls('/scripts');
                        $this->log('Contents of /app:');
                        $builder->ls('/app');
                        $first = false;
                    }

                    if ($waitForFiles) {
                        $this->log('Waiting for file synchronization...');
                        foreach ($waitForFiles as $waitForFile) {
                            $builder->waitForFile($waitForFile);
                        }
                    }

                    $start = time();
                    $builder->run();
                    $total = time() - $start;

                    $this->log($config->getScript().' took '.$total.' seconds');
                } catch (Throwable $e) {
                    throw new RuntimeException($e->getMessage(), 0, $e);
                }
            }
        }
    }
}
