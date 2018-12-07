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

    protected function getCmd(): array
    {
        $cmd = ['up'];
        if ($this->background) {
            $cmd[] = '-d';
        }
        return $cmd;
    }

    protected function cleanup()
    {
        $this->output->writeln("Caught signal, exiting");
        $stop = $this->getAction('stop');
        $stop->run();
    }
}
