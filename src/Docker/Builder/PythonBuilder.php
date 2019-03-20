<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class PythonBuilder extends AbstractBuilder implements BuilderInterface
{
    public function run()
    {
        $this->exec("python {$this->baseFolder}/scripts/".$this->config->getScript());
    }
}
