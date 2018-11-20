<?php
namespace NorthStack\NorthStackClient\Command;

use NorthStack\NorthStackClient\Client\LoginRequiredException;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

trait AutoLoginTrait
{

    public function interact(InputInterface $input, OutputInterface $output)
    {
        parent::interact($input, $output);
        $auth = $this->getApplication()->find('auth:whoami');
        try {
            echo "Testing if you're logged in\n";
            $result = $auth->run(new ArrayInput([]), $output);
        } catch (LoginRequiredException $e) {
            echo "Looks like you need to log in\n";
            $login = $this->getApplication()->find('auth:login');
            return $login->run($input, $output);
        }

    }
}
