<?php

namespace NorthStack\NorthStackClient\AppTypes;

class JekyllType extends BaseType
{
    protected static $args = [
        'frameworkVersion' => [
            'prompt' => 'Jekyll version: ',
            'default' => '^3'
        ]
    ];
}
