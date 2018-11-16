<?php

namespace NorthStack\NorthStackClient\AppTypes;

use NorthStack\NorthStackClient\AppTypes\BaseType;

class StaticType extends BaseType
{
    protected function writePerEnvBuildConfigs()
    {
        $this->writeConfigFile(
            'config/build.json',
            [
                'build-type' => 'builder'
            ]
        );

        $this->writeConfigFile(
          'config/config.json',
          [
            'app-type' => 'static',
            'layout' => 'standard',
            'shared-paths' => ['/']
          ]
        );
    }
}
