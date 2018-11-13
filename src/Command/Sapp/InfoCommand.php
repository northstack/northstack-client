<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\API\Sapp\SappClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;
    /**
     * @var SappClient
     */
    protected $api;

    public function __construct(
        SappClient $api
    )
    {
        parent::__construct('app:info');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Show details about an App')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)')
            ->addArgument('baseFolder', InputArgument::OPTIONAL, 'Path to root of NorthStack folder (contains folder named after app)')
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

        if (empty($args['baseFolder'])) {
            $args['baseFolder'] = getcwd();
        }


        [$sappId] = $this->getSappIdAndFolderByOptions(
            $args['name'],
            $args['environment'],
            $args['baseFolder']
        );

        $r = $this->api->get($this->token->token, $sappId);

        $io = new SymfonyStyle($input, $output);

        $app = json_decode($r->getBody()->getContents());
        $headers = ['Field', 'Value'];
        $rows = [
            ['Name', $app->name],
            ['Cluster', $app->cluster],
            ['Id', $app->id],
            ['OrgId', $app->orgId],
            ['Parent', $app->parentSapp],
            ['Env', $app->environment],
            ['Stack', $app->appType],
            ['Domains', implode("\n", $app->domains->domains)],
        ];

        $io->table($headers, $rows);
    }
}
