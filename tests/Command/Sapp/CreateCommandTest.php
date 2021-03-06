<?php

namespace Test\Command\Sapp;

use GuzzleHttp\Psr7\Response;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use Test\Command\NorthStackCommandTestCase;

class CreateCommandTest extends NorthStackCommandTestCase
{
    public $skipAuthCheck = true;
    public $mockQuestionHelper = true;
    public $mockApp;
    public $mockAppSlug = 'testLocalAppCreate';
    protected $sappClient;

    function getCommandName(): string
    {
        return 'app:create';
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
            ->method('createApp')
            ->willReturn(new Response(201, [], $mockApp));
        $this->mockApp = json_decode($mockApp);

        $wpArgs = [
            '--wpTitle' => $this->mockApp->appName,
            '--wpAdminUser' => $this->mockApp->sharedConfigBuild->framework_config->admin_user,
            '--wpAdminPass' => false,
            '--wpAdminEmail' => $this->mockApp->sharedConfigBuild->framework_config->admin_email,
            '--wpIsMultisite' => $this->mockApp->sharedConfigBuild->framework_config->multisite,
            '--wpMultisiteSubdomains' => $this->mockApp->sharedConfigBuild->framework_config->subdomains,
            '--frameworkVersion' => $this->mockApp->sharedConfigBuild->framework_version,
            '--appSlug' => $this->mockAppSlug,
        ];
        $this->commandTester->execute(array_merge([
            'command' => $this->command->getName(),
            'name' => $this->mockApp->appName,
            'stack' => $this->mockApp->appType,
            'primaryDomain' => $this->mockApp->sapps[0]->primaryDomain,
        ], $wpArgs, $this->getAuthOptionsAsUser()));

        $this->assertStringContainsString('Woohoo!', $this->commandTester->getDisplay());
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

            $this->resetUserSettingsTestFile();
        }
    }
}
