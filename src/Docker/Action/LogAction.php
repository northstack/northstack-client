<?php


namespace NorthStack\NorthStackClient\Docker\Action;

class LogAction extends BaseAction
{
    protected $name = 'log';
    protected $handleSignals = false;

    protected $follow = false;
    protected $tail = "all";

    public function setFollow($follow)
    {
        $this->follow = $follow;
        $this->handleSignals = $follow;
        return $this;
    }

    public function setTail($tail)
    {
        $this->tail = $tail;
        return $this;
    }

    protected function prepare()
    {
        $cmd = ['logs', "--tail={$this->tail}"];
        if ($this->follow) {
            $cmd[] = '--follow';
        }
        $this->container->setCmd($cmd);
    }
}
