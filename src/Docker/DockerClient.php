<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\Docker;
use Docker\API\Model\ContainersCreatePostBody;


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

    public function run($name, $image, $config, $destroy = true)
    {
        $conf = new ContainersCreatePostBody();
        $conf->setImage($image);
        $conf->setAttachStdout(true);
        $conf->setAttachStderr(true);

        $create = $this->docker->containerCreate(
            $conf,
            ['name' => $name]
        );

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
            //echo "STDOUT: {$stdout}";
        });
        $attachStream->onStderr(function ($stderr) {
            //echo "STDERR: {$stderr}";
        });

        $attachStream->wait();

        $this->docker->containerWait($name);
        $this->docker->containerStop($name);
        if ($destroy) {
            $this->docker->containerDelete($name);
        }
    }
}
