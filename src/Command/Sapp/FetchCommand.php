<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\RequestException;
use NorthStack\NorthStackClient\API\Sapp\SappClient;

use NorthStack\NorthStackClient\AppTypes\JekyllType;
use NorthStack\NorthStackClient\AppTypes\StaticType;
use NorthStack\NorthStackClient\AppTypes\WordPressType;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;

use NorthStack\NorthStackClient\Command\UserSettingsCommandTrait;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FetchCommand extends Command
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
        parent::__construct('app:fetch');
        $this->api = $api;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('Fetch and download details about an App')
            ->addArgument('id', InputArgument::REQUIRED, 'Parent App ID');
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $this->api->setDebug();
        }

        $r = $this->api->getAppBySappId($this->token->token, $input->getArgument('id'));
        $output->writeln('Fetching app with environment details...');

        try {
            $sapps = json_decode($r->getBody()->getContents());
        } catch (RequestException $e) {
            if (403 === $e->getCode() || 404 === $e->getCode()) {
                $output->writeln('<error>Unable to fetch app. The app either does not exist, or you do not have access to it.</error>');
                exit;
            }

            throw $e;
        }

        $output->writeln('Successfully fetched app info from API. Continuing...');
        $questionHelper = $this->getHelper('question');
        $appsDir = $this->findDefaultAppsDir($input, $output, $questionHelper);
        $templateArgs = [
            'appName' => $sapps[0]->name,
            'baseDir' => $appsDir,
            'primaryDomain' => $sapps[0]->primaryDomain,
            'cluster' => $sapps[0]->cluster,
        ];

        switch ($sapps[0]->appType) {
            case 'wordpress':
                $appTemplate = new WordPressType($input, $output, $questionHelper, $templateArgs);
                break;
            case 'static':
                $appTemplate = new StaticType($input, $output, $questionHelper, $templateArgs);
                break;
            case 'jekyll':
                $appTemplate = new JekyllType($input, $output, $questionHelper, $templateArgs);
                break;
            default:
                throw new \Exception("Invalid stack {$sapps[0]->appType}");
        }

        $appTemplate->writeConfigs($sapps);

    }
}
