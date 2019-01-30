<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class BashBuilder extends AbstractBuilder implements BuilderInterface
{
    public function run()
    {
        $this->dockerClient->exec($this->containerId, [
            'chmod',
            '+x',
            '/scripts/'.$this->config->getScript(),
        ]);

        $this->exec('/scripts/'.$this->config->getScript());
    }
}
