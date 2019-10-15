<?php

namespace Test\Command\Stacks;

use GuzzleHttp\Psr7\Response;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use Test\Command\NorthStackCommandTestCase;

class FetchCommandTest extends NorthStackCommandTestCase
{
    public $skipAuthCheck = true;
    public $mockQuestionHelper = true;
    public $testDir;
    public $mockApp;
    public $mockAppSlug = 'testLocalAppFetch';
    protected $sappClient;

    function getCommandName(): string
    {
        return 'app:fetch';
    }

    function setUp(): void
    {
        $this->mockCurrentUser();
        $this->mockUserSettings();
        $this->generateMocks([SappClient::class], true);
        $this->sappClient = $this->getMockService(SappClient::class);
        parent::setUp();
    }

    function testExecute()
    {
        $mockApp = file_get_contents(dirname(__FILE__, 3) . '/assets/testapp1-fetch-200-body.json');
        $this->sappClient->expects($this->once())
            ->method('getAppBySappId')
            ->willReturn(new Response(200, [], $mockApp));
        $this->mockApp = json_decode($mockApp);

        $options = [
            '--appSlug' => $this->mockAppSlug,
        ];
        $this->commandTester->execute(array_merge([
            'command' => $this->command->getName(),
            'appId' => $this->mockApp->appName,
        ], $options, $this->getAuthOptionsAsUser(false)));

        $this->assertStringContainsString('Success!', $this->commandTester->getDisplay());
        $this->assertDirectoryExists($this->testDir . '/' . $this->mockAppSlug);
        $this->assertFileIsReadable($this->testDir . '/' . $this->mockAppSlug . '/config/shared-build.json');
        $generatedSharedConfigBuild = json_decode(file_get_contents($this->testDir . '/' . $this->mockAppSlug . '/config/shared-build.json'));
        $this->assertEquals($this->mockApp->sharedConfigBuild, $generatedSharedConfigBuild);
    }

    function tearDown(): void
    {
        // remove the test local app
        $testAppDir = $this->testDir . '/' . $this->mockAppSlug;
        if (is_dir($testAppDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($testAppDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($testAppDir);
        }

        $this->resetUserSettingsTestFile();
    }
}
