<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


class GatsbyContainer extends BuildScriptContainer
{
    protected $cmd = ['/usr/local/bin/gatsby.sh'];
}
