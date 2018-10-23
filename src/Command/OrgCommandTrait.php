<?php
namespace NorthStack\NorthStackClient\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trait OrgCommandTrait
{
    private $currentOrg;
    private $allOrgs;

    protected function addOrgOption()
    {
        /** @var Command $this */
        $this->addOption('org', null, InputOption::VALUE_REQUIRED, 'Org name (or ID) to run this command as');
    }

    protected function setCurrentOrg($org, $required = false)
    {
        if (!$org)
        {
            $default = $this->getDefaultOrg();
            if ($required && !$default)
            {
                throw new \Exception("You must specify an org");
            }
            $this->currentOrg = $default;
            return;
        }

        $foundOrg = $this->getOrg($org);
        if ($foundOrg)
        {
            $this->currentOrg = $foundOrg;
            return;
        }

        throw new \Exception("Org name/ID ({$org}) not found in {$accountFile}");
    }

    protected function getDefaultOrg()
    {
        $orgs = $this->getOrgs();
        $org = null;
        if (array_key_exists('default', $orgs) && array_key_exists($orgs['default'], $orgs))
        {
            $org = $orgs[$orgs['default']];
        }
        return $org;
    }

    protected function getOrgs()
    {
        if ($this->allOrgs)
        {
            return $this->allOrgs;
        }
        $accountFile = getenv('HOME').'/.northstackaccount.json';
        $orgs = [];
        if (file_exists($accountFile))
        {
            $this->allOrgs = json_decode(file_get_contents($accountFile), true);
        }
        return $this->allOrgs;
    }

    protected function getOrg($idOrName)
    {
        $orgs = $this->getOrgs();
        // Locate by orgId
        if (array_key_exists($idOrName, $orgs))
        {
            return $orgs[$idOrName];
        }

        // Locate by org name
        foreach ($orgs as $id => $org)
        {
            if ($id === 'default')
            {
                continue;
            }
            if ($org['name'] === $idOrName)
            {
                return $org;
            }
        }

        return false;
    }

    protected function updateAccountsFile($data)
    {
        $out = array_merge($this->getOrgs(), $data);
        $path = getenv('HOME').'/.northstackaccount.json';
        file_put_contents($path, json_encode($out));
        $this->allOrgs = null;
    }
}
