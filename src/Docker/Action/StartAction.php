<?php


namespace NorthStack\NorthStackClient\Docker\Action;

class StartAction extends BaseAction
{
    protected $name = 'start';

    protected $background = false;

    public function setBackground($value)
    {
        $this->background = $value;
        $this->handleSignals = !$value;
        return $this;
    }

    protected function prepare()
    {
        // set the force-recreate flag to make sure the builds are fresh every time
        $cmd = ['up', '--force-recreate'];

        if ($this->background) {
            $cmd[] = '-d';
        }

        $this->container->setCmd($cmd);
    }

    protected function cleanup()
    {
        $this->output->writeln("Caught signal, exiting");
        $stop = $this->createAction('stop');
        $stop->run();
    }
}
