<?php


namespace NorthStack\NorthStackClient\Enumeration;


use Eloquent\Enumeration\AbstractEnumeration;

class Environment extends AbstractEnumeration
{
    const production = 'prod';
    const development = 'dev';
    const testing = 'test';
}
