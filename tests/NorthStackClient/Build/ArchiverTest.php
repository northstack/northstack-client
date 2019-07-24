<?php
namespace Test\NorthStackClient\Build;

use PHPUnit\Framework\TestCase;
use NorthStack\NorthStackClient\Build\Archiver;
use Alchemy\Zippy\Zippy;

class ArchiverTest extends TestCase
{
    public function testArchive()
    {
        $cwd = getcwd();
        chdir("/tmp");

        $archiver = new Archiver(Zippy::load());

        $archiver->archive('test', __DIR__.'/assets/fakeapp');

        $this->assertFileExists('/tmp/test.tar.gz');
    }

    public function setUp(): void {
        if (file_exists('/tmp/test.tar.gz'))
            unlink('/tmp/test.tar.gz');
    }

    public function tearDown(): void {
        if (file_exists('/tmp/test.tar.gz'))
            unlink('/tmp/test.tar.gz');
    }
}
