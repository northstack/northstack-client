<?php


namespace NorthStack\NorthStackClient\Command\Helpers;


use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

trait AskPhoneTrait
{
    protected function askPhone(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $helper
    )
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        $output->writeln('We use phone verification via text message.');

        $question = new Question('Owner Mobile Phone Number Country Code (1 for US): [1]', '1');
        $countryCode = $helper->ask($input, $output, $question);

        $question = new Question('Owner Mobile Phone Number (For Verification): ');
        $phone = $helper->ask($input, $output, $question);

        $phoneObject = $phoneUtil->parse("+{$countryCode} {$phone}", 'US');

        $formatted = substr($phoneUtil->format($phoneObject, PhoneNumberFormat::RFC3966), 4);
        $question = new ConfirmationQuestion("Is your phone number {$formatted}? [Y/n] ");

        if (!$helper->ask($input, $output, $question)) {
            return $this->askPhone($input, $output, $helper);
        }

        return [$phoneObject->getNationalNumber(), $phoneObject->getCountryCode()];
    }
}
