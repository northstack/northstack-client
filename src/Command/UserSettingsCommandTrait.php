<?php

namespace NorthStack\NorthStackClient\Command;

use NorthStack\NorthStackClient\UserSettingsHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

trait UserSettingsCommandTrait
{
    /**
     * Checks the user settings for a default local apps dir and asks the user to choose one
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $questionHelper
     * @return mixed|null
     */
    public function findDefaultAppsDir(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $appsDir = UserSettingsHelper::get(UserSettingsHelper::KEY_LOCAL_APPS_DIR);

        if (!$appsDir) {
            $askAppLocation = new Question('Enter the parent directory you would NorthStack apps to be created in by default: ', $_SERVER['HOME'] . '/northstack/apps');
            $appsDir = $questionHelper->ask($input, $output, $askAppLocation);
            $maybeReplace = 1;
            $appsDir = 0 === strpos($appsDir, '~') ? str_replace('~', $_SERVER['HOME'], $appsDir, $maybeReplace) : $appsDir;

            // Ensure the directory exists -- if not, try to create it
            if (!is_dir($appsDir)) {
                try {
                    mkdir($appsDir, 0777, true);
                } catch (\Throwable $e) {
                    $output->writeln('There was an error creating the new directory, please create it and try again.');
                    $output->writeln('Error message: ' . $e->getMessage());
                    exit;
                }
            }

            // update the setting in the settings file
            UserSettingsHelper::updateSetting(UserSettingsHelper::KEY_LOCAL_APPS_DIR, $appsDir);

            $output->writeln("Default Apps Directory set: $appsDir");
        }

        return $appsDir;
    }
}
