<?php


namespace NorthStack\NorthStackClient\Build;


use Alchemy\Zippy\Zippy;

class Archiver
{
    /**
     * @var Zippy
     */
    private $zippy;

    public function __construct(Zippy $zippy)
    {
        $this->zippy = $zippy;
    }

    public function archive(string $sappId, string $appFolder): string
    {
        $baseFolder = getcwd();
        $zip = "{$baseFolder}/{$sappId}.tar.gz";

        $this->zippy->create($zip, [
            'app' => "{$appFolder}/app",
        ], true);

        return $zip;
    }

}
