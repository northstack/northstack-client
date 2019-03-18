<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use NorthStack\NorthStackClient\API\Sapp\SappClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use NorthStack\NorthStackClient\Command\UserSettingsCommandTrait;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends Command
{
    use OauthCommandTrait;
    use SappEnvironmentTrait;
    use UserSettingsCommandTrait;
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
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, or dev)');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $this->api->setDebug();
        }

        $args = $input->getArguments();

        [$sappId] = $this->getSappIdAndFolderByOptions(
            $this->findDefaultAppsDir($input, $output, $this->getHelper('question')),
            $args['name'],
            $args['environment']
        );

        $r = $this->api->get($this->token->token, $sappId);
        $app = json_decode($r->getBody()->getContents());

        $io = new SymfonyStyle($input, $output);

        $headers = [
            [new TableCell($app->name . ' (' . $app->environment . ')', ['colspan' => 2])],
        ];
        $rows = [
            ['Type', $app->appType],
            ['Cluster', $app->cluster],
            ['Id', $app->id],
            ['OrgId', $app->orgId],
            ['Created', $app->created],
            ['Updated', $app->updated],
            ['Parent', $app->parentSapp],
            ['Stack', $app->appType],
            ['Internal URL', isset($app->internalUrl) ? $app->internalUrl : ''], // This IF is just temporary because its a new value
            ['Primary Domain', $app->primaryDomain],
            ['Domains', implode("\n", $app->domains)],
            ['Current Release', $app->currentRelease ?: 'No releases found'],
        ];

        $io->table($headers, $rows);
    }
}
