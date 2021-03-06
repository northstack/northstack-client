<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class NodeBuilder extends AbstractBuilder
{
    protected $version;

    protected function installNode()
    {
        $this->version = $this->config->getVersion() ?: 'stable';
        $this->exec('nvm install '. $this->version, ['NVM_DIR=/root/.nvm']);
    }

    public function run()
    {
        $this->installNode();

        $this->exec('nvm run '.$this->version." /scripts/{$this->config->getScript()}");
    }
}
