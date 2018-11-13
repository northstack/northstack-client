<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


trait SappEnvironmentTrait
{
    protected function getSappIdAndFolderByOptions($name, $environment)
    {
        $appFolder = getcwd().'/'.$name;

        if (!file_exists($appFolder)) {
            throw new \RuntimeException("<error>Folder {$appFolder} not found</error>");
        }

        // find sapp id based on environment/app from environment.json
        $envConfig = json_decode(file_get_contents("{$appFolder}/config/environment.json"), true);

        return [$envConfig[$environment], $appFolder];
    }
}
