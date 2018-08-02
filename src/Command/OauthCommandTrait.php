<?php
namespace Pagely\NorthstackClient\Command;

use Pagely\NorthstackClient\API\AuthApi;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pagely\NorthstackClient\OauthToken;

trait OauthCommandTrait
{
    /**
     * @var AuthApi
     */
    protected $authClient;
    protected $token;
    protected $optionalOauth = false;

    protected function addOauthOptions()
    {
        /** @var Command $this */
        $this->addOption('authToken', null, InputOption::VALUE_REQUIRED, 'Access Token')
            ->addOption('authClientId', null, InputOption::VALUE_REQUIRED, 'OAuth Client ID', 2)
            ->addOption('authClientSecret', null, InputOption::VALUE_REQUIRED, 'Client Secret')
            ->addOption('authUsername', null, InputOption::VALUE_REQUIRED, 'Username')
            ->addOption('authPassword', null, InputOption::VALUE_REQUIRED, 'Password')
            ->addOption('authMfa', null, InputOption::VALUE_REQUIRED, 'MFA Code')
            ->addOption('authScope', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Scopes', [])
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->token = new OauthToken();

        /** @var Command $this */
        parent::initialize($input, $output);

        $clientId = $input->getOption('authClientId');
        if ($token = $input->getOption('authToken')) {
            $this->token->token = $token;
            return;
        }

        if (($username = $input->getOption('authUsername')) && $password = $input->getOption('authPassword')) {
            $r = $this->authClient->login($username, $password, $input->getOption('authMfa'), $input->getOption('authScope'));
        } elseif ($secret = $input->getOption('authClientSecret')) {
            $r = $this->authClient->clientLogin($clientId, $secret, $input->getOption('authScope'));
        }

        if (isset($r)) {
            $data = json_decode($r->getBody()->getContents(), true);
            $this->token->updateFromResponse($data);
        }

        if (!$this->token->token && !$this->optionalOauth) {
            throw new \Exception('Not enough information to make an access token');
        }
    }
}
