<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\AuthApi;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var SappClient
     */
    protected $api;

    public function __construct(SappClient $api, AuthApi $authApi)
    {
        parent::__construct('sapp:create');
        $this->api = $api;
        $this->authClient = $authApi;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('orgId', InputArgument::REQUIRED, 'Org ID')
            ->addArgument('environment', InputArgument::REQUIRED, 'Environment (prod, test, dev)')
            ->addArgument('cluster', InputArgument::REQUIRED, 'Cluster name')
            ->addArgument('primaryDomain', InputArgument::REQUIRED, 'Primary Domain')
            ->addOption('altdomain', 'd', InputOption::VALUE_IS_ARRAY|InputOption::VALUE_REQUIRED, 'Extra domains')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'JSON blob of configuration')
            ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->debug = true;
        }

        $args = $input->getArguments();
        $domains = $input->getOption('domain') ?
            json_encode($input->getOption('altdomain')) :
            null;
        $config = $input->getOption('config');

        try {
            $r = $this->api->createApp(
                $this->token->token,
                $args['name'],
                $args['orgId'],
                $args['environment'],
                $args['cluster'],
                $args['primaryDomain'],
                $domains,
                $config
            );
        } catch (ClientException $e) {
            $output->writeln('<error>App Create Failed</error>');
            $output->writeln($e->getResponse()->getBody()->getContents());
            return;
        }

        $data = json_decode($r->getBody()->getContents());
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
    }
}
