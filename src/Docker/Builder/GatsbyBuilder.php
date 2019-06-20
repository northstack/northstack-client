<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class GatsbyBuilder extends NodeBuilder
{
    public function run()
    {
        parent::run();

        $version = $this->version ? "@{$this->version}" : '';
        $this->exec("npx gatsby{$version} build");
    }
}
