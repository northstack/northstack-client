<?php


namespace NorthStack\NorthStackClient\Command\UserSettings;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\UserSettingsHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetSettingsCommand extends Command
{
    /**
     * @var UserSettingsHelper
     */
    private $userSettingsHelper;

    public function __construct(UserSettingsHelper $userSettingsHelper)
    {
        parent::__construct('settings:info');
        $this->userSettingsHelper = $userSettingsHelper;
    }

    public function configure()
    {
        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $settings = $this->userSettingsHelper->getSettings();

        if (!$settings) {
            $output->writeln('No local NorthStack settings found.');
            return;
        }

        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        $headers = ['Key', 'Value'];
        $rows = [];
        foreach ($settings as $key => $value) {
            $rows[] = [
                $key,
                var_export($value, true), // this can then handle sub-arrays or other types of data we might store in the future
            ];
        }
        $io->table($headers, $rows);
    }
}
