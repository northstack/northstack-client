<?php

namespace NorthStack\NorthStackClient\AppTypes;

class GatsbyType extends BaseType
{
    protected static $args = [
        'frameworkVersion' => [
            'prompt' => 'Gatsby version: ',
            'default' => '2.5.0'
        ]
    ];
}
