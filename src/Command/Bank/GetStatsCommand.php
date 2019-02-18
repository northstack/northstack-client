<?php
namespace NorthStack\NorthStackClient\Command\Bank;

use NorthStack\NorthStackClient\API\Bank\BankClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GetStatsCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var BankClient
     */
    protected $api;

    public function __construct(
        BankClient $api
    )
    {
        parent::__construct('stats:get');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Get Stats for an App')
            ->addArgument('id', InputArgument::REQUIRED, 'App id')
            ->addArgument('type', InputArgument::REQUIRED, 'Stats Type (access, worker)')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'From date YYYY-MM-DD', date('Y-m-d', strtotime('-30 days')))
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'To date YYYY-MM-DD', date('Y-m-d'))
            ->addOption('field-set', null, InputOption::VALUE_REQUIRED, 'Which fields to display: basic, all, percentile', 'basic')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug())
        {
            $this->api->setDebug();
        }

        $args = $input->getArguments();
        $options = $input->getOptions();

        $io = new SymfonyStyle($input, $output);

        $r = $this->api->statsForApp(
            $this->token->token,
            $args['id'],
            $args['type'],
            $options['to'],
            $options['from']
        );
        $stats = json_decode($r->getBody()->getContents(), true);

        $fieldSet['all'] = array_keys($stats['data'][0]);
        $fieldSet['basic'] = [
            'time',
            'type',
            'scheme',
            'method',
            'httpCode',
            'cacheStatus',
            'count',
            'average',
            'median',
            'max',
            'min',
        ];
        $fieldSet['percentile'] = [
            'time',
            'type',
            'scheme',
            'method',
            'httpCode',
            'cacheStatus',
            'count',
            'p05',
            'p25',
            'p50',
            'p75',
            'p95',
        ];

        $headers = $fieldSet[$options['field-set']];
        $rows = [];
        foreach($stats['data'] as $row) {
            $r = [];
            foreach($fieldSet[$options['field-set']] as $key) {
                $r[] = $row[$key];
            }
            $rows[] = $r;
        }

        $io->table($headers, $rows);
    }
}
