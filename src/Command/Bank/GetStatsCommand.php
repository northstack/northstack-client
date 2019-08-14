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
            ->setDescription('Get Stats for an Org')
            ->addArgument('type', InputArgument::REQUIRED, 'Stats Type [traffic, workers]')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'From date, any format recognizable by strtotime', '-1 hour')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'To date, any format recognizable by strtotime', 'now')
            ->addOption('window', null, InputOption::VALUE_REQUIRED, 'Aggregate data into this interval (minutes)', '1')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Org ID')
            ->addOption('appId', null, $OPTION_ARRAY, 'App id')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'json hash of filters {"HttpCode":["200"]}')
            ->addOption('field-filter', null, InputOption::VALUE_REQUIRED, 'regex to whitelist non time fields with')
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
        $fieldFilter = $input->getOption('field-filter');

        $filters = [
            'fromDate' => $input->getOption('from'),
            'toDate' => $input->getOption('to'),
        ];

        if (!empty($input->getOption('filter'))) {
            $filterOptions = json_decode($input->getOption('filter'));
            if (json_last_error()) {
                echo "Bad Filter: ".json_last_error_msg()."\n";
                exit(1);
            }
            foreach($filterOptions as $k => $v) {
                $filters[$k] = (array)$v;
            }
        }

        $r = $this->api->statsForOrg(
            $this->token->token,
            $orgId,
            $args['type'],
            $filters
        );
        $stats = json_decode($r->getBody()->getContents(), true);
        echo json_encode($stats, JSON_PRETTY_PRINT);
        exit;

        foreach($stats['data']['series'] as $series) {
            $table = new Table($output);

            $columns = $series['columns'];
            if (!empty($fieldFilter)) {
                foreach($columns as $k => $v) {
                    if ($k == 0) {
                        continue;
                    }
                    if (!preg_match($fieldFilter, $v)) {
                        unset($columns[$k]);
                    }
                }
            }
            $table->setHeaders($columns);

            $rows = $series['values'];
            if (!empty($fieldFilter)) {
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
