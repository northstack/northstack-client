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

    public function containerExists($name)
    {
        try {
            $this->docker->containerInspect($name);
            return true;
        } catch (ContainerInspectNotFoundException $e) {
            return false;
        }
    }

    public function containerIsRunning($name)
    {
        try {
            $info = $this->docker->containerInspect($name);
            print_r($info);
            return true;
        } catch (ContainerInspectNotFoundException $e) {
            return false;
        }
    }

    public function createContainer(
        string $name,
        Container $conf,
        $recreate = true
    )
    {
        if ($this->containerExists($name))
        {
            $this->stop($name, $recreate);

            if (!$recreate) {
                return true;
            }
        }

        $conf
            ->setAttachStdout(true)
            ->setAttachStderr(true)
        ;

        return $this->docker->containerCreate(
            $conf,
            ['name' => $name]
        );

    }

    public function run($name, $config, $destroy = true)
    {
        $this->createContainer($name, $config, true);

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

    public function stop($name, $destroy = false)
    {
        $this->docker->containerStop($name);
        $this->docker->containerWait($name);

        if ($destroy) {
            $this->docker->containerDelete($name);
        }
    }

}
