<?php

namespace Test\Command;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\NorthStackTestCase;

abstract class NorthStackCommandTestCase extends NorthStackTestCase
{
    /** @var CommandTester */
    protected $commandTester;
    /** @var \Symfony\Component\Console\Command\Command */
    protected $command;
    public $skipAuthCheck = false;
    /** @var MockObject */
    protected $whoAmI;

    abstract function getCommandName(): string;

    function setUp(): void
    {
        if ($this->skipAuthCheck) {
            $this->generateMocks([\NorthStack\NorthStackClient\Command\Auth\WhoAmICommand::class], true);
            $this->whoAmI = $this->getMockService(\NorthStack\NorthStackClient\Command\Auth\WhoAmICommand::class);
            $this->whoAmI->expects($this->any())->method('getName')->willReturn('auth:whoami');
            $this->whoAmI->expects($this->any())->method('isEnabled')->willReturn(true);
            $this->whoAmI->expects($this->any())->method('getDefinition')->willReturn('whoami');
            $this->whoAmI->expects($this->any())->method('getAliases')->willReturn([]);
            $this->whoAmI->expects($this->any())->method('run')->willReturn(true);
        }
        parent::setUp();
        $this->command = $this->application->find($this->getCommandName());
        $this->commandTester = new CommandTester($this->command);
    }
}
