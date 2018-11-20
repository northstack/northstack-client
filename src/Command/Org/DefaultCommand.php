<?php

namespace NorthStack\NorthStackClient\Command\Org;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OrgCommandTrait;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{

    use OrgCommandTrait;

    protected $skipLoginCheck = true;

    public function __construct()
    {
        parent::__construct('org:default');
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Get or set the default org for client commands')
        ;
        $this->addOrgOption();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $orgId = $input->getOption('org');
        if ($orgId)
        {
            $org = $this->getOrg($orgId);
            if ($org)
            {
                $this->updateAccountsFile(['default' => $org['id']]);
                $output->writeln("Default org set to {$org['id']}");
                return;
            }

            $output->writeln("<error>Org {$orgId} not found. See `org:list` for a listing of local orgs</error>");
            return;
        }

        $default = $this->getDefaultOrg();
        if ($default)
        {
            $output->writeln("The current default org is: {$default['id']}");
            return;
        }

        $output->writeln("No default org set--run with `--org <orgName|orgId>` to set it.");
    }
}
