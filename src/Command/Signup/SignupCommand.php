<?php


namespace NorthStack\NorthStackClient\Command\Signup;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class SignupCommand extends Command
{
    /**
     * @var OrgsClient
     */
    protected $api;

    public function __construct(OrgsClient $api)
    {
        parent::__construct('signup');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('NorthStack Signup');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->debug = true;
        }

        $helper = $this->getHelper('question');

        $question = new Question('Organization Name');
        $orgName = $helper->ask($input, $output, $question);

        $question = new Question('Username');
        $username = $helper->ask($input, $output, $question);

        $question = (new Question('Password'))->setHidden(true);
        $password = $helper->ask($input, $output, $question);

        $question = new Question('Owner Name');
        $name = $helper->ask($input, $output, $question);

        $question = new Question('Owner Email');
        $email = $helper->ask($input, $output, $question);

        try {
            $r = $this->api->signup(
                $orgName,
                $username,
                $password,
                $name,
                $email
            );
        } catch (ClientException $e) {
            $output->writeln('<error>Signup failed</error>');
            $output->writeln($e->getResponse()->getBody()->getContents());
            return;
        }

        $data = json_decode($r->getBody()->getContents());
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
    }
}
