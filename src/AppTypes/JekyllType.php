<?php

namespace NorthStack\NorthStackClient\AppTypes;

class JekyllType extends BaseType
{
    protected function writePerEnvBuildConfigs()
    {
        $this->writeConfigFile(
            'config/build.json',
            [
                'build-type' => 'builder',
                'build-scripts' => [],
                'framework-version' => '3',
            ]
        );

        $this->writeConfigFile(
            'config/config.json',
            [
                'app-type' => 'jekyll',
                'layout' => 'standard',
                'shared-paths' => ['/'],
            ]
        );
    }
}
