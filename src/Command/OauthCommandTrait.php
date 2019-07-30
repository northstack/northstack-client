<?php

namespace NorthStack\NorthStackClient\Command;

use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use NorthStack\NorthStackClient\OauthToken;
use GuzzleHttp\Exception\ClientException;

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
        return $this->addOption('authToken', null, InputOption::VALUE_REQUIRED, 'Access Token')
            ->addOption('authClientId', null, InputOption::VALUE_REQUIRED, 'OAuth Client ID', 2)
            ->addOption('authClientSecret', null, InputOption::VALUE_REQUIRED, 'Client Secret')
            ->addOption('authUsername', null, InputOption::VALUE_REQUIRED, 'Username')
            ->addOption('authPassword', null, InputOption::VALUE_REQUIRED, 'Password')
            ->addOption('authMfa', null, InputOption::VALUE_REQUIRED, 'MFA Code')
            ->addOption('authScope', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Scopes', []);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->token = new OauthToken();

        /** @noinspection PhpUndefinedClassInspection */
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

        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (isset($r)) {
            $data = json_decode($r->getBody()->getContents(), true);
            $this->token->updateFromResponse($data);
        }

        if (!$this->token->token && !$this->optionalOauth) {
            throw new \Exception('Not enough information to make an access token');
        }
    }

    protected function currentUser(OrgsClient $orgsClient)
    {
        if (empty($this->token->token)) {
            return null;
        }

        $parts = explode('.', $this->token->token);
        [$type, $id] = explode(':', json_decode(base64_decode($parts[1]))->sub);

        if ($type === 'Pagely.Model.Orgs.OrgUser') {
            $r = $orgsClient->getUser($this->token->token, $id);
            $user = json_decode($r->getBody()->getContents());
            $user->type = $type;
            return $user;
        }

        return null;
    }

    protected function requireLogin(OrgsClient $orgsClient)
    {
        try {
            $user = $this->currentUser($orgsClient);
        } catch (ClientException $e) {
        }

        if (!isset($user)) {
            throw new \Exception("You must be logged in to perform this action");
        }

        return $user;
    }
}
