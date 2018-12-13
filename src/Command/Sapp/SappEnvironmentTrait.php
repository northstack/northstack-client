<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\JSON\Merger;

trait SappEnvironmentTrait
{
    protected function getSappIdAndFolderByOptions($name, $environment)
    {
        $appFolder = getcwd() . '/' . $name;

        if (!file_exists($appFolder)) {
            throw new \RuntimeException("<error>Folder {$appFolder} not found</error>");
        }

        // find sapp id based on environment/app from environment.json
        $envConfig = json_decode(file_get_contents("{$appFolder}/config/environment.json"), true);

        return [$envConfig[$environment], $appFolder];
    }

    protected function getSappFromWorkingDir($env = 'prod')
    {
        $cwd = getcwd();
        $name = basename($cwd);

        array_map(
            function ($path) use ($cwd) {
                $path = $cwd . $path;
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

        $conf = $this->mergeConfigs($cwd, $env, true);
        $environments = json_decode(file_get_contents("{$cwd}/config/environment.json"));
        $conf['name'] = $name;
        $conf['id'] = $environments->{$env};
        $conf['env'] = $env;
        return $conf;
    }

    protected function mergeConfigs(string $appFolder, string $environment, $decode = false)
    {
        $configs = [
            'config' => file_get_contents("{$appFolder}/config/config.json"),
            'build' => file_get_contents("{$appFolder}/config/build.json"),
            'domains' => '{}',
        ];

        // the gateway file doesn't have to exist at all
        if (file_exists("{$appFolder}/config/gateway.json")) {
            $configs['gateway'] = file_get_contents("{$appFolder}/config/gateway.json");
        }

        foreach ($configs as $file => $json) {
            $envFile = "{$appFolder}/config/{$environment}/{$file}.json";
            if (file_exists($envFile)) {
                $configs[$file] = Merger::merge($json, file_get_contents($envFile));
            } else {
                $configs[$file] = Merger::merge($json, '{}');
            }

            if ($decode) {
                $configs[$file] = json_decode($configs[$file]);
            }
        }
        return $configs;
    }
}
