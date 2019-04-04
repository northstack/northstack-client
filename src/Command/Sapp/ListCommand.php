<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
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

        $sections = $rows = [];
        foreach ($body->data as $sapp) {
            switch($sapp->environment) {
                case 'prod':
                    $parentId = $sapp->id;
                    break;
                default:
                    $parentId = $sapp->parentSapp;
                    break;
            }

            $sections[$parentId][$sapp->environment] = $sapp;
        }

        $count = 1;
        $headers = ['<fg=magenta>ID</>', '<fg=magenta>Env</>', '<fg=magenta>Primary Domain</>'];
        foreach ($sections as $parentId => $section) {
            $rows[] = [
                new TableCell("<fg=cyan>{$section['prod']->name} ({$section['prod']->appType})</>", ['colspan' => 2]),
                "<fg=cyan>{$section['prod']->orgId}</>"
            ];
            $rows[] = [' ']; // add a little space to help the sapp title be more visible
            foreach ($section as $sapp) {
                $rows[] = [$sapp->id, $sapp->environment, $sapp->primaryDomain];
            }
            if ($count % 12) {
                $rows[] = new TableSeparator();
            } elseif ($count == count($body->data)) {
                $rows[] = $headers;
            } else {
                $rows[] = new TableSeparator();
                $rows[] = $headers;
                $rows[] = new TableSeparator();
            }
            $count++;
        }

        $table = new Table($output);
        $table->setStyle('borderless');
        $table->setHeaders($headers);
        $table->setRows($rows);

        $table->render();
    }
}
