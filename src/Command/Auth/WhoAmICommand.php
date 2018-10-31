<?php
namespace NorthStack\NorthStackClient\Command\Auth;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

class WhoAmICommand extends Command
{
    use OauthCommandTrait;
    protected $api;

    public function __construct(OrgsClient $api)
    {
        parent::__construct('auth:whoami');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Details about the currently logged in user')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $io = new SymfonyStyle($input, $output);
        if (!empty($this->token->token))
        {
            $user = $this->requireLogin($this->api);

            if ($user)
            {
                $io->writeln("current logged in as {$user->type}:{$user->id}");
                $io = new SymfonyStyle($input, $output);

                $headers = ['Field', 'Value'];
                $rows = [
                    ['Id', $user->id],
                    ['Username', $user->username],
                    ['First', $user->firstName],
                    ['Last', $user->lastName],
                    ['Email', $user->email],
                ];
                $io->table($headers, $rows);

                $r = $this->api->getCurrentUserPermissions($this->token->token);
                $permissions = json_decode($r->getBody()->getContents());
                $headers = ['Org Id', 'Permission', 'Added By', 'Updated'];
                $rows = [];
                foreach($permissions->data as $org)
                {
                    $rows[] = [$org->orgId, $org->permissions, $org->addedByUser, $org->updated];
                }
                $io->table($headers, $rows);
            }
        }
    }
}
