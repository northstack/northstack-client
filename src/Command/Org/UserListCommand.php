<?php
namespace NorthStack\NorthStackClient\Command\Org;

use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\OrgCommandTrait;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserListCommand extends Command
{
    use OrgCommandTrait;
    use OauthCommandTrait;
    /**
     * @var OrgsClient
     */
    protected $api;
    /**
     * @var Client
     */
    private $guzzle;

    public function __construct(
        OrgsClient $api
    )
    {
        parent::__construct('org:user:list');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('List all the users in an orgization')
            ->addOauthOptions()
            ->addOrgOption();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $args = $input->getArguments();
        $this->setCurrentOrg($input->getOption('org'), true);
        $r = $this->api->listUsers($this->token->token, $this->currentOrg['id']);

        $io = new SymfonyStyle($input, $output);

        $users = json_decode($r->getBody()->getContents());
        $headers = ['User Id', 'Username', 'First', 'Last', 'Email', 'Permissions'];
        $rows =[];
        foreach($users->data as $user)
        {
            $rows[] = [
                $user->id,
                $user->username,
                $user->firstName,
                $user->lastName,
                $user->email,
                $user->orgPermissions[0]->permissions
            ];
        }

        $io->table($headers, $rows);
    }
}
