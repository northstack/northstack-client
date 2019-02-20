<?php

namespace NorthStack\NorthStackClient\Command\Auth;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\Command\Helpers\AskPhoneTrait;
use NorthStack\NorthStackClient\Command\Helpers\ValidationErrors;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Question\Question;

class EnableTwoFactorCommand extends Command
{
    use OauthCommandTrait;
    use AskPhoneTrait;
    use ValidationErrors;

    protected $api;

    public function __construct(OrgsClient $api)
    {
        parent::__construct('auth:enable-2fa');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Enable Two-Factor Authentication');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $this->api->setDebug(true);
        }

        if (!empty($this->token->token)) {
            $user = $this->requireLogin($this->api);

            $output->writeln('You should have Authy installed on your phone to make multi-factor easy!');
            $output->writeln('');

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            [$phone, $phoneCountry] = $this->askPhone($input, $output, $helper);

            try {
                $result = $this->api->requestTwoFactor($this->token->token, $user->id, "{$phoneCountry} {$phone}");
            } catch (ClientException $e) {
                if ($e->getResponse()->getStatusCode() === 422) {
                    $this->displayValidationErrors($e->getResponse(), $output);
                    return;
                }
                throw $e;
            }
            $data = json_decode($result->getBody()->getContents());

            $output->writeln("If you are *not* using Authy, please open {$data->qrCode} in your browser and use your two-factor app to scan it.");

            $this->checkCode($input, $output, $helper, $user);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param QuestionHelper $helper
     * @param $user
     * @param int $tries
     */
    protected function checkCode(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $helper,
        $user,
        $tries = 0
    ): void
    {
        $question = (new Question('Please enter the code from your two-factor app:'))
            ->setValidator(function ($answer) {
                if (empty($answer)) {
                    throw new \Exception("Code cannot be empty");
                }
                if (6 !== strlen($answer)) {
                    throw new \Exception("Code should be six digits");
                }
                return $answer;
            })
            ->setMaxAttempts(3);

        $code = $helper->ask($input, $output, $question);

        try {
            $this->api->enableTwoFactor($this->token->token, $user->id, $code);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 422) {
                $this->displayValidationErrors($e->getResponse(), $output);
                if ($tries > 4) {
                    $output->writeln("<error>After {$tries} tries, maybe something went wrong. Please restart this process.</error>");
                    return;
                }
                $this->checkCode($input, $output, $helper, $user, ++$tries);
                return;
            }
            throw $e;
        }

        $output->writeln('Two-Factor authentication enabled! Thanks for being security-minded!');
    }
}
