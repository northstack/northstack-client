<?php

namespace NorthStack\NorthStackClient\AppTypes;

class StaticType extends BaseType
{
    protected function writePerEnvBuildConfigs()
    {
        $this->writeConfigFile(
            'config/build.json',
            [
                'build-type' => 'builder',
                'build-scripts' => [],
            ]
        );

        $this->writeConfigFile(
            'config/config.json',
            [
                'app-type' => 'static',
                'layout' => 'standard',
                'shared-paths' => ['/'],
            ]
        );
    }
}
