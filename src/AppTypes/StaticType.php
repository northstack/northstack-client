<?php

namespace NorthStack\NorthStackClient\AppTypes;

class StaticType extends BaseType
{
    protected function writePerEnvBuildConfigs()
    {
        $this->writeConfigFile(
            'config/build.json',
            [
                'build_type' => 'builder',
                'build_scripts' => [],
            ]
        );

        $this->writeConfigFile(
            'config/config.json',
            [
                'app-type' => 'static',
                'layout' => 'standard',
                'shared_paths' => ['/'],
            ]
        );
    }
}
