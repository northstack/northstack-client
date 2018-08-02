<?php
namespace Pagely\NorthstackClient\Command\Auth;

use Pagely\NorthstackClient\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Pagely\NorthstackClient\OauthToken;

class LogoutCommand extends Command
{
    public function __construct($name = 'auth:logout')
    {
        parent::__construct($name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $token = new OauthToken();
        $token->deleteSaved();
        $output->writeln('Logged out. Bye!');
    }
}
