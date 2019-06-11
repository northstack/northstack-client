<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


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

        $dir = $dir . "/$name";

        array_map(
            function ($path) use ($dir) {
                $path = $dir . $path;
                if (!file_exists($path)) {
                    throw new \Exception("Command must be executed inside an app directory (missing: {$path})");
                }
            },
            [
                '/app/public',
                '/config/build.json',
                '/config/config.json',
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
        if (file_exists("{$appFolder}/config/gateway.json")) {
            $configs['gateway'] = file_get_contents("{$appFolder}/config/gateway.json");
        }

        foreach ($configs as $file => $json) {
            $envFile = "{$appFolder}/config/{$environment}/{$file}.json";
            if (file_exists($envFile)) {
                // fix domains if they are in the old format
                if ($file === 'domains') {
                    $domains = json_decode(file_get_contents($envFile));
                    if (!is_array($domains)) {
                        if (is_object($domains) && $domains->domains) {
                            $json = json_encode($domains->domains);
                            file_put_contents($envFile, $json);
                        } else {
                            throw new \RuntimeException('Invalid structure in: ' . $envFile);
                        }
                    }
                }
                $configs[$file] = Merger::merge($json, file_get_contents($envFile));
            }

            if ($decode) {
                $configs[$file] = json_decode($configs[$file]);
            }
        }
        return $configs;
    }
}
