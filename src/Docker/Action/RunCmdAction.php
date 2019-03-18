<?php


namespace NorthStack\NorthStackClient\Docker\Action;

class RunCmdAction extends BaseAction
{
    protected $name = 'cmd';

    protected $cmd = [];

    public function setCmd($cmd)
    {
        $this->cmd = $cmd;
    }

    protected function prepare()
    {
        $this->container->setCmd($this->cmd);
    }

}
