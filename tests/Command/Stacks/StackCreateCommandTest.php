<?php

namespace Test\Command\Stacks;

use GuzzleHttp\Psr7\Response;
use NorthStack\NorthStackClient\API\Infra\StackClient;
use Test\Command\NorthStackCommandTestCase;

class StackCreateCommandTest extends NorthStackCommandTestCase
{
    public $skipAuthCheck = true;
    public $mockQuestionHelper = true;
    public $mockApp;
    public $mockAppSlug = 'testLocalAppCreate';
    protected $stacksClient;

    public function getCommandName(): string
    {
        return 'stack:create';
    }

    public function setUp(): void
    {
        $this->mockCurrentUser();
        $this->mockUserSettings();
        $this->generateMocks([StackClient::class], true);
        $this->stacksClient = $this->getMockService(StackClient::class);
        parent::setUp();
    }

    public function testExecute()
    {
        $mockApp = file_get_contents(dirname(__FILE__, 3) . '/assets/testapp1-fetch-200-body.json');
        $this->stacksClient->expects($this->once())
            ->method('createStack')
            ->willReturn(new Response(201, [], $mockApp));
        $this->mockApp = json_decode($mockApp);

        $this->commandTester->execute(array_merge([
            'command' => $this->command->getName(),
            'label' => $this->mockApp->appName,
            'type' => $this->mockApp->appType,
        ], $this->getAuthOptionsAsUser()));

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
