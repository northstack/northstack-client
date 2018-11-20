<?php


namespace NorthStack\NorthStackClient\Command\Org;

use GuzzleHttp\Client;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\OrgCommandTrait;
use NorthStack\NorthStackClient\Command\AutoLoginTrait;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends Command
{
    use OrgCommandTrait;
    use OauthCommandTrait;
    use AutoLoginTrait;
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
        parent::__construct('org:info');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Show details about an Org')
            ->addOauthOptions()
            ->addOrgOption();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $this->setCurrentOrg($input->getOption('org'), true);

        $r = $this->api->get($this->token->token, $this->currentOrg['id']);

        $io = new SymfonyStyle($input, $output);

        $org = json_decode($r->getBody()->getContents());
        $headers = ['Field', 'Value'];
        $rows = [
            ['Id', $org->id],
            ['Name', $org->name],
            ['Created', $org->created],
            ['Updated', $org->updated],
            ['OwnerId', $org->ownerId],
        ];

        $io->table($headers, $rows);
    }
}
