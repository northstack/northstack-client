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

class GetStatsDimensionsCommand extends Command
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
        parent::__construct('stats:dimensions');
        $this->api = $api;
        $this->orgs = $orgs;
        $this->orgAccountHelper = $orgAccountHelper;
    }

    public function configure()
    {
        $OPTION_ARRAY = InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED;
        parent::configure();
        $this
            ->setDescription('Get Stat Dimensions')
            ->addArgument('type', InputArgument::REQUIRED, 'Stats Type [traffic, workers]')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'From date, any format recognizable by strtotime', '-1 day')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'To date, any format recognizable by strtotime', 'now')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Org ID')
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'table|json', 'table')
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

        $r = $this->api->dimensionsForOrg(
            $this->token->token,
            $orgId,
            $args['type'],
            $filters
        );
        $stats = json_decode($r->getBody()->getContents(), true);
        if ($input->getOption('output') == 'json') {
            echo json_encode($stats, JSON_PRETTY_PRINT);
            return;
        }

        $makeRows = function($in) {
            $out = [];
            foreach($in as $val) {
                $out[] = [$val];
            }
            return $out;
        };

        $table = new Table($output);
        $table->setRows($makeRows($stats['data']['columns']));
        $table->setHeaders(["Fields"]);
        $table->render();

        $makeRows = function($in) {
            $out = [];
            foreach($in as $k => $val) {
                $out[] = [$k, implode("\n",$val)];
            }
            return $out;
        };

        $table = new Table($output);
        $table->setRows($makeRows($stats['data']['dimensions']));
        $table->setHeaderTitle("Dimensions");
        $table->setHeaders(["Tag", "Values"]);
        $table->render();
    }
}
