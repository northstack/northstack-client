<?php


namespace NorthStack\NorthStackClient\Command\Org;

use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends Command
{
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
        parent::__construct('org:info');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Show details about an Org')
            ->addArgument('id', InputArgument::REQUIRED, 'Org Id')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug(true);
        }

        $args = $input->getArguments();
        $r = $this->api->get($args['id']);

        $io = new SymfonyStyle($input, $output);

        $org = json_decode($r->getBody()->getContents());
        print_r($org);
        $headers = ['Field', 'Value'];
        $rows = [
            ['Id', $org->id],
            ['Name', $org->name],
        ];

        $io->table($headers, $rows);
    }
}
