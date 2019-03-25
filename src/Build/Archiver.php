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
        $doArchive = [
            'app' => "{$appFolder}/app",
        ];

        foreach (
            [
                'scripts',
                'config',
            ] as $checkFile
        ) {
            if (file_exists("{$appFolder}/{$checkFile}")) {
                $doArchive[$checkFile] = "{$appFolder}/{$checkFile}";
            }
        }

        $this->zippy->create($zip, $doArchive, true);

        return $zip;
    }

}
