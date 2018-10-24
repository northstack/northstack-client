<?php


namespace NorthStack\NorthStackClient;


class PathHelper
{
    private $inDocker;
    private $workdir;

    public function __construct()
    {
        $this->inDocker = getenv('NS_DOCKER') ? true : false;
        $this->workdir = getenv('NS_USER_PWD') ? getenv('NS_USER_PWD') : getcwd();
        $this->workdir = $this->normalizePath($this->workdir);
    }

    public function displayPath($path)
    {
        if ($this->inDocker)
        {
            $path = $this->fromDockerPath($path);
        }

        return $path;
    }

    public function validPath($path)
    {
        $path = $this->normalizePath($path);

        if ($this->inDocker)
        {
            $path = $this->toDockerPath($path);
        }
        return $path;
    }

    protected function normalizePath($path)
    {
        if (file_exists($path))
        {
            $path = realpath($path);
        }
        $path = rtrim($path, '/');
        return $path;
    }

    protected function startsWith($path, $prefix)
    {
        return (strpos($path, $prefix) === 0);
    }

    protected function fromDockerPath($path)
    {
        return preg_replace('@^/current@', $this->workdir, $path);
    }

    protected function toDockerPath($path)
    {
        if ($this->startswith($path, '/home') || $this->startsWith($path, '/current'))
        {
            return $path;
        } elseif ($this->startswith($path, $this->workdir))
        {
            $re = "@^{$this->workdir}@";
            return preg_replace($re, '/current/', $path);
        }

        throw new \Exception("Invalid path ({$path}). Please use a path in your home directory or in the current working directory.");
    }

}
