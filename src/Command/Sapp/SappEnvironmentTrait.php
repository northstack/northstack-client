<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\Build\AppConfig;
use NorthStack\NorthStackClient\Build\BuildConfig;
use NorthStack\NorthStackClient\JSON\Merger;
use NorthStack\NorthStackClient\UserSettingsHelper;

trait SappEnvironmentTrait
{
    protected function getSappIdAndFolderByOptions($appDir, $name, $environment)
    {
        $appFolder = $appDir . '/' . $name;

        if (!file_exists($appFolder)) {
            throw new \RuntimeException("<error>Folder {$appFolder} not found</error>");
        }

        // find sapp id based on environment/app from environment.json
        $envConfig = json_decode(file_get_contents("{$appFolder}/config/environment.json"), true);

        return [$envConfig[$environment], $appFolder];
    }

    protected function getSapp($name, $env = 'prod')
    {
        $dir = UserSettingsHelper::get(UserSettingsHelper::KEY_LOCAL_APPS_DIR);
        if (!$dir) {
            throw new \Exception('No local apps directory set. ', 400);
        }

        $dir .= "/$name";

        array_map(
            function ($path) use ($dir) {
                $path = $dir . $path;
                if (!file_exists($path)) {
                    throw new \Exception("No app directory found (missing: {$path})");
                }
            },
            [
                '/app',
                '/config/shared-build.json',
                '/config/shared-config.json',
                '/config/environment.json',
                "/config/{$env}"
            ]
        );

        $conf = $this->mergeConfigs($dir, $env, true);
        $environments = json_decode(file_get_contents("{$dir}/config/environment.json"));
        $conf['name'] = $name;
        if ($env !== 'local') {
            $conf['id'] = $environments->{$env};
        } else {
            $conf['id'] = 'local';
        }
        $conf['env'] = $env;
        return $conf;
    }

    protected function mergeConfigs(string $appFolder, string $environment, $decode = false)
    {
        $configs = [
            'config' => file_get_contents("{$appFolder}/config/shared-config.json"),
            'build' => file_get_contents("{$appFolder}/config/shared-build.json"),
            'domains' => '[]',
        ];

        // the gateway file doesn't have to exist at all
        if (file_exists("{$appFolder}/config/shared-gateway.json")) {
            $configs['gateway'] = file_get_contents("{$appFolder}/config/shared-gateway.json");
        } else {
            $configs['gateway'] = '';
        }

        foreach ($configs as $configType => $json) {
            $envFile = "{$appFolder}/config/{$environment}/{$configType}.json";
            if (file_exists($envFile)) {
                $fileContents = file_get_contents($envFile);
                // check to see if the file is empty -- no need to merge if it is
                if (!empty(json_decode($fileContents))) {
                    $configs[$configType] = Merger::merge($json, $fileContents);
                }
            }

            switch ($configType) {
                case 'config':
                    $configs['configObject'] = new AppConfig(json_decode($configs[$configType], true));
                    break;
                case 'build':
                    $configs['buildObject'] = new BuildConfig(json_decode($configs[$configType], true));
                    break;
            }

            if ($decode) {
                $configs[$configType] = json_decode($configs[$configType]);
            }
        }

        return $configs;
    }
}
