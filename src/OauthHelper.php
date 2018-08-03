<?php
namespace NorthStack\NorthStackClient;

use NorthStack\NorthStackClient\API\AuthApi;

class OauthHelper
{
    protected $api;
    public function __construct(AuthApi $api)
    {
        $this->api = $api;
    }

    protected function currentFileFromFileExistance()
    {
        $home = getenv('HOME');

        $tokenFiles = ['.northstacklogin', '.northstackclientlogin'];
        foreach ($tokenFiles as $tokenFile)
        {
            $file = "{$home}/{$tokenFile}";
            if (file_exists($file))
            {
                $currentFile = $tokenFile;
            }
        }

        return isset($currentFile) ? $currentFile : false;
    }
}
