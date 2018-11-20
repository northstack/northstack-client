<?php
namespace NorthStack\NorthStackClient\Command;

use NorthStack\NorthStackClient\Client\LoginRequiredException;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait LoginRequiredTrait
{
    protected $skipLoginCheck = false;

    public function run(InputInterface $input, OutputInterface $output)
    {
        if ($input->isInteractive() && !$this->skipLoginCheck) {
            $this->checkLogin($output);
        }
        parent::run($input, $output);
    }

    protected function checkLogin(OutputInterface $output)
    {
        $auth = $this->getApplication()->find('auth:whoami');
        try {
            $result = $auth->run(new ArrayInput([]), new NullOutput());
        } catch (LoginRequiredException $e) {
            $output->writeln("No active NorthStack token found. Please log in.");
            $login = $this->getApplication()->find('auth:login');
            $login->run(new ArrayInput([]), $output);
        }

    }
}
