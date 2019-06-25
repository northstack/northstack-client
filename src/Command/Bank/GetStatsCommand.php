<?php
namespace NorthStack\NorthStackClient\Command\Bank;

use NorthStack\NorthStackClient\API\Bank\BankClient;

use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use NorthStack\NorthStackClient\OrgAccountHelper;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

class GetStatsCommand extends Command
{
    use OauthCommandTrait;
    /**
     * @var BankClient
     */
    protected $api;

    /**
     * @var OrgsClient
     */
    private $orgs;


    /**
     * @var OrgAccountHelper
     */
    protected $orgAccountHelper;

    public function __construct(
        BankClient $api,
        OrgsClient $orgs,
        OrgAccountHelper $orgAccountHelper
    )
    {
        parent::__construct('stats:get');
        $this->api = $api;
        $this->orgs = $orgs;
        $this->orgAccountHelper = $orgAccountHelper;
    }

    public function configure()
    {
        $OPTION_ARRAY = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED;
        parent::configure();
        $this
            ->setDescription('Get Stats for an App')
            ->addArgument('type', InputArgument::REQUIRED, 'Stats Type [traffic]')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'From date, any format recognizable by strtotime', '-1 hour')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'To date, any format recognizable by strtotime', 'now')
            ->addOption('window', null, InputOption::VALUE_REQUIRED, 'Aggregate data into this interval (minutes)', '1')
            ->addOption('field-set', null, InputOption::VALUE_REQUIRED, 'all, percentile, basic', 'basic')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Org ID')
            ->addOption('appId', null, $OPTION_ARRAY, 'App id')
            ->addOption('httpCode', null, $OPTION_ARRAY, 'Http codes (200,404,etc)')
            ->addOption('method', null, $OPTION_ARRAY, 'Http method, (GET, POST,etc)')
            ->addOption('cacheStatus', null, $OPTION_ARRAY, 'Cache Status, (HIT, MISS, BYPASS)')
            ->addOption('scheme', null, $OPTION_ARRAY, 'http scheme, (http, https)')
            ->addOption('host', null, $OPTION_ARRAY, 'hostname, (example.com, www.example.com)')
            ->addOption('contentType', null, $OPTION_ARRAY, 'Content Type, (assets,content)')
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
        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];
        $user = $this->requireLogin($this->orgs);

        $filters = [
            'fromDate' => $input->getOption('from'),
            'toDate' => $input->getOption('to'),
        ];

        $map = [
            'appId' => 'sappIds',
            'window' => 'window',
            'scheme' => 'scheme',
            'method' => 'method',
            'host' => 'host',
            'contentType' => 'type',
            'httpCode' => 'httpCode',
        ];

        foreach($map as $option => $filter) {
            $optionValue = $input->getOption($option);
            if ($optionValue !== false && (!is_array($optionValue) || count($optionValue) > 1)) {
                $filters[$filter] = $input->getOption($option);
            }
        }

        $r = $this->api->statsForOrg(
            $this->token->token,
            $orgId,
            $args['type'],
            $filters
        );
        $stats = json_decode($r->getBody()->getContents(), true);

        $skip = [];
        switch($input->getOption('field-set')) {
        case 'basic':
            $skip = ['p05','p25','p50','p75','p95'];
            break;
        case 'percentile':
            $skip = ['max','min','total','average','median'];
            break;
        }

        foreach($stats['data']['series'] as $series) {
            $table = new Table($output);

            $columns = $series['columns'];
            foreach($skip as $i) {
                $toDel = array_search($i,$columns);
                if ($toDel !== false) {
                    unset($columns[$toDel]);
                }
            }
            $table->setHeaders($columns);

            $rows = $series['values'];
            if (count($skip) > 0) {
                foreach($rows as $i => $row) {
                    foreach($row as $k => $v) {
                        if (!isset($columns[$k])) {
                            unset($row[$k]);
                        }
                    }
                    $rows[$i] = $row;
                }
            }
            $table->setRows($rows);
            $title = [];
            foreach($series['tags'] as $k => $v) {
                $title[] = "$k = $v";
            }
            $table->setHeaderTitle(implode(', ',$title));
            $table->render();
        }
    }
}
