<?php

namespace NorthStack\NorthStackClient\Docker;

use Docker\API\Exception\ImageInspectNotFoundException;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\Docker;

use Docker\API\Exception\ContainerCreateNotFoundException;
use Docker\API\Exception\ContainerCreateConflictException;
use Docker\API\Exception\ContainerWaitNotFoundException;
use Docker\API\Exception\ContainerDeleteConflictException;
use Docker\API\Exception\ContainerStopNotFoundException;
use Docker\Stream\CreateImageStream;
use Docker\Stream\DockerRawStream;

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

    public function pullImage($name, $force = false)
    {
        [$image, $tag] = explode(':', $name, 2);

        if ($force) {
            // remove old image if it is found
            try {
                $this->docker->imageInspect($name);
                $this->docker->imageDelete($name);
            } catch (ImageInspectNotFoundException $e) {}

            /** @var CreateImageStream $createImageStream */
            $createImageStream = $this->docker->imageCreate(
                '',
                ['fromImage' => $image, 'tag' => $tag]
            );
            $createImageStream->wait();
            return;
        }

        try {
            $this->docker->imageInspect($name);
        } catch (ImageInspectNotFoundException $e) {
            /** @var CreateImageStream $createImageStream */
            $createImageStream = $this->docker->imageCreate(
                '',
                ['fromImage' => $image, 'tag' => $tag]
            );
            $createImageStream->wait();
        }
    }

    public function run($name)
    {
        return $this->docker->containerStart($name);
    }

    public function exec($name, array $cmd)
    {
        $execConfig = new ContainersIdExecPostBody();
        $execConfig->setTty(true);
        $execConfig->setAttachStdout(true);
        $execConfig->setAttachStderr(true);
        $execConfig->setAttachStdin(true);
        $execConfig->setCmd($cmd);

        $execid = $this->docker->containerExec($name, $execConfig)->getId();
        $execStartConfig = new ExecIdStartPostBody();
        $execStartConfig->setDetach(false);
        // Execute the command

        $stream = $this->docker->execStart($execid,$execStartConfig);
        //var_dump($stream);die();
        // To see the output stream of the 'exec' command

        $stdoutText = "";
        $stderrText = "";
        $stream->onStdout(function ($stdout) use (&$stdoutText) {
            echo $stdout;
        });
        $stream->onStderr(function ($stderr) use (&$stderrText) {
            echo "err: ".$stderr."\n";
        });
        $stream->wait();
    }

    /**
     * @param $name
     * @param bool $attachInput
     * @return \Psr\Http\Message\ResponseInterface|null|DockerRawStream
     */
    public function attachOutput($name, $attachInput = false)
    {
        return $this->docker->containerAttach(
            $name,
            [
                'stream' => true,
                'stdin'  => $attachInput,
                'stdout' => true,
                'stderr' => true,
                'logs'   => false,
            ]
        );
    }

    public function stop($name, $destroy = false, $timeout = 10)
    {
        try {
            $return = $this->docker->containerStop($name, ['t' => $timeout]);
            if ($destroy) {
                return $this->deleteContainer($name);
            }
            return $return;
        } catch (ContainerStopNotFoundException $e)
        {
            return true;
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
