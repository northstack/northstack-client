<?php

namespace NorthStack\NorthStackClient\AppTypes;

class JekyllType extends BaseType
{
    protected $args = [
        'frameworkVersion' => [
            'prompt' => 'Jekyll version: ',
            'default' => '^3'
        ]
    ];
}
