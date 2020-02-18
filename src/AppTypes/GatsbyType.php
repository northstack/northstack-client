<?php

namespace NorthStack\NorthStackClient\AppTypes;

use NorthStack\NorthStackClient\UserInput\BasicInput;

class GatsbyType extends BaseType
{
    public static function getArgs()
    {
        return [
            new BasicInput('frameworkVersion', 'Gatsby version', '2.5.0')
        ];
    }
}
