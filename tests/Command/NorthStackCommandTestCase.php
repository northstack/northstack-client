<?php

namespace Test\Command;

use GuzzleHttp\Psr7\Response;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Tester\CommandTester;
use Test\NorthStackTestCase;

abstract class NorthStackCommandTestCase extends NorthStackTestCase
{
    public $skipAuthCheck = false;
    public $mockQuestionHelper = false;
    public $mockQuestions = [];

    /** @var CommandTester */
    protected $commandTester;
    /** @var \Symfony\Component\Console\Command\Command */
    protected $command;
    /** @var MockObject */
    protected $whoAmI;
    /** @var MockObject */
    protected $orgsClient;

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

        if ($this->mockQuestionHelper) {
            $this->mockQuestionHelper();
        }
        $this->commandTester = new CommandTester($this->command);
    }

    abstract function getCommandName(): string;

    function mockCurrentUser($type = 'Pagely.Model.Orgs.OrgUser')
    {
        $mockUser = file_get_contents(dirname(__FILE__, 2) . '/assets/testuser-get-200.json');

        $this->generateMocks([OrgsClient::class], true);
        $this->orgsClient = $this->getMockService(OrgsClient::class);
        $this->orgsClient->expects($this->any())
            ->method('getUser')
            ->willReturn(new Response(200, [], $mockUser));
    }

    /**
     * @param string $type
     * @param string $orgId
     * @return array
     */
    function getAuthOptionsAsUser($type = 'Pagely.Model.Orgs.OrgUser', $orgId = 'testorg')
    {
        $testToken = new \stdClass();
        $testToken->sub = $type . ':testUser';

        return [
            '--orgId' => 'testorg',
            '--authClientId' => 'test',
            '--authToken' => 'test.' . base64_encode(json_encode($testToken)),
        ];
    }

    function mockQuestionHelper()
    {
        $this->generateMocks([QuestionHelper::class]);
        /** @var MockObject|QuestionHelper $helper */
        $helper = $this->getMockService(QuestionHelper::class);
        $helper->expects($this->any())
            ->method('ask')
            ->will($this->returnCallback([$this, 'mockQuestionAsk']));

        $this->command->getHelperSet()->set($helper, 'question');

    }

    function mockQuestionAsk(InputInterface $input, OutputInterface $output, Question $question)
    {
        static $order = -1;

        $order = $order + 1;
        $text = $question->getQuestion();

        // you can check against $text to see if this is the question you want to handle
        // and you can check against $order (starts at 0) for the order the questions come in

        $output->write($text . ' => ');

        // handle a question
//        if (strpos($text, 'overwrite') !== false) {
//            $response = true;
//
//            // handle another question
//        } elseif (strpos($text, 'api_key') !== false) {
//            $response = 'api-test-key';
//        }

        $response = 'testresponse';

        if (isset($response) === false) {
            throw new \RuntimeException('Was asked for input on an unhandled question: ' . $text);
        }

        $output->writeln(print_r($response, true));
        return $response;
    }
}
