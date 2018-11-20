<?php


namespace NorthStack\NorthStackClient\Command\Org;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\OrgCommandTrait;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListOrgsCommand  extends Command
{
    use OrgCommandTrait;

    protected $skipLoginCheck = true;

    public function __construct()
    {
        parent::__construct('org:list');
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('List local orgs')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $orgs = $this->getOrgs();

        $io = new SymfonyStyle($input, $output);

        $headers = ['id', 'name', 'created', 'updated', 'ownerId'];
        $rows = [];
        foreach ($orgs as $id => $org)
        {
            if ($id === 'default')
            {
                continue;
            }
            $row = [];
            foreach ($headers as $key)
            {
                $row[] = $org[$key];
            }
            $rows[] = $row;
        };

        $io->table($headers, $rows);
    }
}
