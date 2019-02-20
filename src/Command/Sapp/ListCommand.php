<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\OrgAccountHelper;


class ListCommand extends Command
{
    use OauthCommandTrait;

    /**
     * @var SappClient
     */
    private $api;
    private $orgs;

    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;

    public function __construct(SappClient $api, OrgsClient $orgs, OrgAccountHelper $orgAccountHelper)
    {
        parent::__construct("app:list");
        $this->api = $api;
        $this->orgs = $orgs;
        $this->orgAccountHelper = $orgAccountHelper;

    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('List Northstack Apps')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'App name to filter by')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $user = $this->requireLogin($this->orgs);

        $r = $this->api->Search(
            $this->token->token,
            $input->getOption('name'),
            $orgId
        );

        $body = json_decode($r->getBody()->getContents());
        $io = new SymfonyStyle($input, $output);

        $headers = ['Type', 'Name', 'Primary Domain', 'Env', 'Id'];
        foreach ($body->data as $sapp) {
            $rows[] = [
                $sapp->appType,
                $sapp->name,
                $sapp->primaryDomain,
                $sapp->environment,
                $sapp->id
            ];
        }
        $io->table($headers, $rows);
    }

    protected function commandName(): string
    {
        return 'app:deploy-legacy';
    }
}
