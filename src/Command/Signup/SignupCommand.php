<?php


namespace NorthStack\NorthStackClient\Command\Signup;

use GuzzleHttp\Exception\ClientException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
        $this->setDescription('NorthStack Signup')
            ->addArgument('baseFolder', InputArgument::OPTIONAL, 'Folder that apps will be created in (defaults to current dir)');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $helper = $this->getHelper('question');

        $question = new Question('Organization Name: ');
        $orgName = $helper->ask($input, $output, $question);

        $question = new Question('Username (usually email address): ');
        $username = $helper->ask($input, $output, $question);

        $question = (new Question('Password: '))->setHidden(true);
        $password = $helper->ask($input, $output, $question);

        $question = new Question('Owner First Name: ');
        $firstName = $helper->ask($input, $output, $question);

        $question = new Question('Owner Last Name: ');
        $lastName = $helper->ask($input, $output, $question);

        $question = new Question('Owner Email: ');
        $email = $helper->ask($input, $output, $question);

        [$phone, $phoneCountry] = $this->askPhone($input, $output, $helper);

        try {
            $r = $this->api->signup(
                $orgName,
                $username,
                $password,
                $firstName,
                $lastName,
                $email,
                $phone,
                $phoneCountry
            );
        } catch (ClientException $e) {
            $output->writeln('<error>Signup failed</error>');
            $output->writeln($e->getResponse()->getBody()->getContents());
            return;
        }

        $data = ['org' => json_decode($r->getBody()->getContents())];

        $nsdir = $input->getArgument('baseFolder');
        if ($nsdir === '.' || empty($nsdir)) {
            $nsdir = getcwd();
        } elseif (!file_exists($nsdir)) {
            $this->mkDirIfNotExists($nsdir);
        }

        file_put_contents("$nsdir/.account.json", json_encode($data));

        $output->writeln([
            "Success! Welcome to NorthStack, {$username}.",
            "Your account details have been written to {$nsdir}/.account.json for safekeeping.",
            "You can sign into your account by running `northstack auth:login {$username}`."
        ]);
    }
    protected function mkDirIfNotExists($path) {
        if (
            !file_exists($path) &&
            !mkdir($concurrentDirectory = $path) && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created',
                $concurrentDirectory));
        }
    }

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
