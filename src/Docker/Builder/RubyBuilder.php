<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class RubyBuilder extends AbstractBuilder implements BuilderInterface
{
    protected $version;

    public function run()
    {
        $this->exec('ruby /scripts/'.$this->config->getScript());
    }
}
