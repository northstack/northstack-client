<?php


namespace NorthStack\NorthStackClient\Docker\Action;

class StopAction extends BaseAction
{
    protected $handleSignals = false;

    protected $name = 'stop';

    protected function prepare()
    {
        $this->container->setCmd(['stop']);
    }

}
