<?php


namespace NorthStack\NorthStackClient\Docker\Builder;


interface BuilderInterface
{
    public function ls($path);
    public function run();
}
