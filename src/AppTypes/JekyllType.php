<?php

namespace NorthStack\NorthStackClient\AppTypes;

use NorthStack\NorthStackClient\UserInput\BasicInput;

class JekyllType extends BaseType
{
    public static function getArgs()
    {
        return [
            new BasicInput('frameworkVersion', 'Jekyll version', '3')
        ];
    }
}
