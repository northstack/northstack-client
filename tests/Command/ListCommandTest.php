<?php
namespace Test\Command;

class ListCommandTest extends NorthStackCommandTestCase
{
    function getCommandName(): string
    {
        return 'list';
    }

    function testExecute()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),],[
        ]);

        $this->assertStringContainsString('Available commands:', $this->commandTester->getDisplay());
    }
}
