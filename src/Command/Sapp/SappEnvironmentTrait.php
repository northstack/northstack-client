<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


trait SappEnvironmentTrait
{
    protected function getSappIdAndFolderByOptions($name, $environment, $baseFolder)
    {
        if (empty($baseFolder)) {
            $baseFolder = getcwd();
        }

        if (!file_exists($baseFolder)) {
            throw new \RuntimeException("<error>Folder {$baseFolder} not found</error>");
        }

        // calculate app folder
        if (strpos($baseFolder, './') === 0) {
            $baseFolder = getcwd().substr($baseFolder, 1);
        } elseif ($baseFolder === '.') {
            $baseFolder = getcwd();
        } elseif (strpos($baseFolder, '~/') === 0) {
            $baseFolder = getenv('HOME').substr($baseFolder, 1);
        }
        $baseFolder = rtrim($baseFolder, '/');

        $appFolder = $baseFolder.'/'.$name;

        if (!file_exists($appFolder)) {
            throw new \RuntimeException("<error>Folder {$appFolder} not found</error>");
        }

        // find sapp id based on environment/app from environment.json
        $envConfig = json_decode(file_get_contents("{$appFolder}/config/environment.json"), true);

        return [$envConfig[$environment], $appFolder];
    }
}
