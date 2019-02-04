<?php
namespace NorthStack\NorthStackClient\Command\Org\User;

use GuzzleHttp\Client;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\OrgCommandTrait;

use NorthStack\NorthStackClient\Enumeration\OrgPermission;
use NorthStack\NorthStackClient\Enumeration\OrgPermissionName;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UserAddCommand extends Command
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
        parent::__construct('org:user:add');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Add user to organization')
            ->addArgument('firstName', InputArgument::REQUIRED)
            ->addArgument('lastName', InputArgument::REQUIRED)
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('permissions', InputArgument::REQUIRED|InputArgument::IS_ARRAY)
            ->addOauthOptions()
            ->addOrgOption();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug();
        }

        $this->setCurrentOrg($input->getOption('org'), true);
        $r = $this->api->addUser($this->token->token, $this->currentOrg['id'], $input->getArgument('firstName'), $input->getArgument('lastName'), $input->getArgument('permissions'), $input->getArgument('email'));

        $io = new SymfonyStyle($input, $output);

        $user = json_decode($r->getBody()->getContents());
        $headers = ['User Id', 'Username', 'First', 'Last', 'Email', 'Permissions'];
        $rows =[];
        $rows[] = [
            $user->id,
            $user->username,
            $user->firstName,
            $user->lastName,
            $user->email,
            implode("\n", $this->getPermissionNames($user->orgPermissions[0]->permissions)),
        ];

        $io->table($headers, $rows);
    }

    protected function getPermissionNames(int $perms)
    {
        $list = [];
        /**
         * @var OrgPermission $orgPermission
         */
        foreach (OrgPermission::members() as $key => $orgPermission) {
            if ($perms & $orgPermission->value()) {
                $list[] = OrgPermissionName::memberByKey($key)->value();
            }
        }

        return $list;
    }
}
