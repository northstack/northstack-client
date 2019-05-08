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
    protected function writePerEnvBuildConfigs()
    {
        $this->writeConfigFile(
            'config/build.json',
            [
                'build_type' => 'builder',
                'build_scripts' => [],
                'framework_version' => $this->config['frameworkVersion'],
            ]
        );

        $this->writeConfigFile(
            'config/config.json',
            [
                'app_type' => 'jekyll',
                'layout' => 'standard',
                'shared-paths' => ['/'],
            ]
        );
    }
}
