<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\Docker;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Exception\ContainerInspectNotFoundException;

use Docker\API\Exception\ContainerCreateNotFoundException;
use Docker\API\Exception\ContainerCreateConflictException;
use Docker\API\Exception\ContainerWaitNotFoundException;
use Docker\API\Exception\ContainerDeleteConflictException;
use Docker\API\Exception\ContainerStopNotFoundException;

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

    public function wait($name, $condition = 'not-running')
    {
        return $this->docker->containerWait($name, ['condition' => $condition]);
    }

    public function deleteContainer($name, $forceStop = false)
    {
        try {
            $this->docker->containerDelete($name);
            $this->wait($name, 'removed');
            return true;
        } catch (ContainerWaitNotFoundException $e)
        {
            return true;
        } catch (ContainerDeleteConflictException $e)
        {
            if ($forceStop) {
                return $this->stop($name, true);
            }
            throw new ContainerRunningException($name);
        }
    }

    public function createContainer(
        string $name,
        Container $conf,
        $destroyIfExists = false,
        $stopIfExists = false
    )
    {
        try {
            $this->docker->containerCreate(
                $conf,
                ['name' => $name]
            );
            return true;

        } catch (ContainerCreateConflictException $e)
        {
            if ($destroyIfExists)
            {
                $this->deleteContainer($name, $stopIfExists);
                return $this->createContainer($name, $conf);
            }

            throw new ContainerExistsException($name);
        } catch (ContainerCreateNotFoundException $e)
        {
            $this->pullImage($conf->getImage());
            return $this->createContainer(
              $name,
              $conf,
              $destroyIfExists,
              $stopIfExists
            );
        }
    }

    public function pullImage($name)
    {
        [$image, $tag] = explode(':', $name, 2);
        $this->docker->imageCreate('',
            [
                'fromImage' => $image,
                'tag' => $tag,
            ]
        );
    }

    public function run($name)
    {
        return $this->docker->containerStart($name);
    }

    public function attachOutput($name)
    {
        return $this->docker->containerAttach(
            $name,
            [
                'stream' => true,
                'stdin'  => false,
                'stdout' => true,
                'stderr' => true,
                'logs'   => false,
            ]
        );
    }

    public function stop($name, $destroy = false, $timeout = 10)
    {
        try {
            return $this->docker->containerStop($name, ['t' => $timeout]);
        } catch (ContainerStopNotFoundException $e)
        {
            return true;
        }

        try {
            $this->wait($name);
        } catch (ContainerWaitNotFoundException $e)
        {
            return true;
        }

        if ($destroy) {
            return $this->deleteContainer($name, false);
        }
    }

    public function signal($name, $signal)
    {
        $this->docker->containerKill($name, ['signal' => $signal]);
    }

    public function getLabels($name)
    {
        return $this->docker->containerInspect($name)
            ->getConfig()->getLabels();
    }
}
