<?php


namespace NorthStack\NorthStackClient\Enumeration;


use Eloquent\Enumeration\AbstractEnumeration;

class BuildScriptType extends AbstractEnumeration
{
    const PHP = 'php';
    const NODE = 'node';
    const BASH = 'bash';
    const PYTHON = 'python';
    const RUBY = 'ruby';
}
