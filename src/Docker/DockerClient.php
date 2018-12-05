<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\Docker;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Exception\ContainerInspectNotFoundException;

class DockerClient
{
    /**
     * @var Docker
     */
    private $docker;

    public function __construct()
    {
        $this->docker = Docker::create();
    }
    public function getClient()
    {
        return $this->docker;
    }

    public function pullImage(string $image)
    {
        if ($this->hasImage($image)) {
            return true;
        }

        $res = $this->docker->imageCreate('', [ 'fromImage' => $image])->wait();
        return $this->hasImage($image);
    }

    public function hasImage(string $name)
    {
        $images = $this->listImages(['reference' => [$name]]);
        return count($images) > 0;
    }

    public function listImages(array $filters = [])
    {
        return $this->docker->imageList([
            'filters' => json_encode($filters)
        ]);
    }

    public function containerExists($nameOrId)
    {
        try {
            $this->docker->containerInspect($nameOrId);
            return true;
        } catch (ContainerInspectNotFoundException $e) {
            return false;
        }
    }

    public function createContainer(
        string $name,
        string $image,
        array $config,
        $recreate = true
    )
    {
        if ($this->containerExists($name)) {
            if (!$recreate) {
                return true;
            }
            $this->docker->containerDelete($name);
        }

        $conf = new ContainersCreatePostBody();
        $conf
            ->setImage($image)
            ->setAttachStdout(true)
            ->setAttachStderr(true)
        ;

        foreach ($config as $section => $value) {
            $method = "set{$section}";
            if (method_exists($conf, $method)) {
                $conf->{$method}($value);
            } else {
                throw new \Exception("Unknown container creation config section: {$section}");
            }
        }

        return $this->docker->containerCreate(
            $conf,
            ['name' => $name]
        );

    }

    public function run($name, $image, $config, $destroy = true)
    {
        $this->createContainer($name, $image, $config, true);

        $attachStream = $this->docker->containerAttach(
            $name,
            [
                'stream' => true,
                'stdout' => true,
                'stderr' => true,
            ]
        );

        $this->docker->containerStart($name);

        $attachStream->onStdout(function ($stdout) {
            echo $stdout;
        });
        $attachStream->onStderr(function ($stderr) {
            echo $stderr;
        });

        $attachStream->wait();

        $this->docker->containerWait($name);
        $this->docker->containerStop($name);
        if ($destroy) {
            $this->docker->containerDelete($name);
        }
    }

    public function stop($name)
    {
        $this->docker->containerStop($name);
        $this->docker->containerWait($name);
    }

    public function exec($name, $cmd)
    {
    }
}
