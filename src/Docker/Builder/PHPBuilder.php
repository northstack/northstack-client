<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class PHPBuilder extends AbstractBuilder implements BuilderInterface
{
    public function run()
    {
        $this->exec("php /scripts/{$this->config->getScript()}");
    }
}
