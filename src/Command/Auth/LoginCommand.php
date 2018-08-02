<?php
namespace Pagely\NorthstackClient\Command\Auth;

use Pagely\NorthstackClient\Command\Command;
use GuzzleHttp\Exception\BadResponseException;
use Pagely\NorthstackClient\API\AuthApi;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Pagely\NorthstackClient\OauthToken;
use Symfony\Component\Console\Question\Question;

class LoginCommand extends Command
{
    protected $api;

    public function __construct(AuthApi $api)
    {
        parent::__construct('auth:login');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Login and get save access token')
            ->addArgument('username', InputArgument::REQUIRED)
            ->addOption('scope', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Scope keys', [])
            ->addOption('show', 's', InputOption::VALUE_NONE, 'Just show the token, do not save it!')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->debug = true;
        }

        $question = new Question('Password: ');
        $question->setHidden(true);

        $helper = $this->getHelper('question');
        $password = $helper->ask($input, $output, $question);

        $question = new Question('MFA: ');
        $question->setHidden(true);

        $mfa = $helper->ask($input, $output, $question);

        try {
            $r = $this->api->login($input->getArgument('username'), $password, $mfa, $input->getOption('scope'));
        } catch (BadResponseException $e) {
            $output->writeln('<error>Invalid Login</error>');
            $output->writeln($e->getResponse()->getBody()->getContents());
            return;
        }

        if ($input->getOption('show')) {
            $data = json_decode($r->getBody()->getContents());
            $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
        } else {
            $token = new OauthToken();
            $token->saveRaw($r->getBody()->getContents());
            $output->writeln('Logged in');
       }
    }
}
