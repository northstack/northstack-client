<?php


namespace NorthStack\NorthStackClient\Enumeration;


use Eloquent\Enumeration\AbstractEnumeration;

class BuildScriptType extends AbstractEnumeration
{
    const PHP = 'PHP';
    const NODE = 'NODE';
    const BASH = 'BASH';
    const PYTHON = 'PYTHON';
    const RUBY = 'RUBY';
}
