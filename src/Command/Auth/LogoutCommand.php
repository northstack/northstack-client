<?php
namespace NorthStack\NorthStackClient\Command\Auth;

use NorthStack\NorthStackClient\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use NorthStack\NorthStackClient\OauthToken;

class LogoutCommand extends Command
{
    public function __construct($name = 'org:logout')
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
