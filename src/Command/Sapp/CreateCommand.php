<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\API\Sapp\SecretsClient;
use NorthStack\NorthStackClient\AppTypes\JekyllType;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\Command\UserSettingsCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;

use NorthStack\NorthStackClient\AppTypes\StaticType;
use NorthStack\NorthStackClient\AppTypes\WordPressType;

use NorthStack\NorthStackClient\UserSettingsHelper;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\HelpCommand;

class CreateCommand extends Command
{
    use OauthCommandTrait;
    use UserSettingsCommandTrait;
    /**
     * @var SappClient
     */
    protected $api;

    protected $orgs;
    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;
    /**
     * @var SecretsClient
     */
    private $secretsClient;

    public function __construct(
        SappClient $api,
        OrgsClient $orgs,
        OrgAccountHelper $orgAccountHelper,
        SecretsClient $secretsClient
    )
    {
        parent::__construct('app:create');
        $this->api = $api;
        $this->orgs = $orgs;
        $this->orgAccountHelper = $orgAccountHelper;
        $this->secretsClient = $secretsClient;
    }

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription('NorthStack App Create')
            ->addArgument('name', InputArgument::REQUIRED, 'App name')
            ->addArgument('primaryDomain', InputArgument::REQUIRED, 'Primary Domain')
            ->addArgument('stack', InputArgument::REQUIRED, 'Application stack type (one of: [wordpress, static, jekyll])')
            ->addArgument('stack', InputArgument::REQUIRED, 'Application stack type (one of: [wordpress, static, jekyll])')
            ->addOption('frameworkVersion', null, InputOption::VALUE_REQUIRED, 'Framework version (if not static app)')
            ->addOption('cluster', null, InputOption::VALUE_REQUIRED, 'Deployment location', 'dev-us-east-1')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
            ->addOption('useDefaultLocation', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
        ;
        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $this->api->setDebug();
        }

        $args = $input->getArguments();
        $options = $input->getOptions();

        $questionHelper = $this->getHelper('question');
        $nsdir = $this->findDefaultAppsDir($input, $output, $questionHelper);

        $appPath = "{$nsdir}/{$args['name']}";

        if (file_exists($appPath)) {
            $output->writeln("Folder for app {$args['name']} already exists at {$appPath}");
            return;
        }

        $orgId = $input->getOption('orgId') ?: $this->orgAccountHelper->getDefaultOrg()['id'];

        $user = $this->requireLogin($this->orgs);

        $appTemplate = null;
        $templateArgs = [
            'appName' => $args['name'],
            'baseDir' => $appPath,
            'primaryDomain' => $args['primaryDomain'],
            'cluster' => $options['cluster'],
            'accountUsername' => $user->username,
            'accountEmail' => $user->email,
            'frameworkVersion' => $options['frameworkVersion'],
        ];

        $stack = strtoupper($args['stack']);
        switch ($stack) {
            case 'WORDPRESS':
                $appTemplate = new WordPressType($input, $output, $questionHelper, $templateArgs);
                break;
            case 'STATIC':
                $appTemplate = new StaticType($input, $output, $questionHelper, $templateArgs);
                break;
            case 'JEKYLL':
                $appTemplate = new JekyllType($input, $output, $questionHelper, $templateArgs);
                break;
            default:
                throw new \Exception("Invalid stack {$args['stack']}");
        }
        $appTemplate->promptForArgs();

        if ($appTemplate->getFrameworkConfig()) {
            $options['frameworkConfig'] = $appTemplate->getFrameworkConfig();
        }

        try {
            /**
             * @TODO: Save the initial build configs on create, otherwise we don't know what the user chose if they don't run a deploy and delete their local files!
             */
            $r = $this->api->createApp(
                $this->token->token,
                $args['name'],
                $orgId,
                $options['cluster'],
                $args['primaryDomain'],
                strtoupper($args['stack']),
                !empty($options['frameworkVersion']) ? $options['frameworkVersion'] : null,
                !empty($options['frameworkConfig']) ? $options['frameworkConfig'] : null

            );
        } catch (ClientException $e) {
            $i = $e->getResponse()->getStatusCode();
            if ($i === 401) {
                $output->writeln('<error>Please Log in and try again</error>');
            } else {
                $output->writeln('<error>App Create Failed</error>');
                $output->writeln($e->getResponse()->getBody()->getContents());
            }
            return;
        }
        $data = json_decode($r->getBody()->getContents());
        $sapps = $data->data;

        // Go ahead and try to set the secret for initial WP admin pass
        if (! empty($appTemplate->config['wpAdminPass'])) {
            $output->writeln('Setting initial WP admin password secrets...');
            /**
             * @TODO: we should throw a warning if the chosen admin pass has a single quote
             * @ref: https://github.com/wp-cli/wp-cli/issues/5089
             */
            try {
                // @TODO: build a `setMultiple` secrets endpoint to avoid multiple calls here
                foreach ($sapps as $sapp) {
                    $this->secretsClient->setSecret(
                        $this->token->token,
                        $sapp->id,
                        'wp_initial_admin_pass',
                        $appTemplate->config['wpAdminPass']
                    );
                    $output->writeln(' ...' . $sapp->environment . ' initial secret set');
                }
            } catch (ClientException $e) {
                $output->writeln('<error>There was an issue setting the initial WordPress admin password. \n 
One will be set for you on your first app deploy, which can be retrieved by fetching the secret `wp_initial_admin_pass`.</error>');
            }
        }


        $appTemplate->writeConfigs($sapps);
        $this->printSuccess($input, $output, $sapps, $appPath);
    }

    function printSuccess($input, $output, $sapps, string $appPath)
    {
        $io = new SymfonyStyle($input, $output);
        $appName = $sapps[0]->name;
        $io->newLine();
        $io->writeln("Woohoo! Your NorthStack app ({$appName}) was created successfully. Here are your prod, testing, and dev environments:");

        foreach ($sapps as $sapp) {
            $headers = [
                [new TableCell($sapp->name . ' (' . $sapp->environment . ')', ['colspan' => 2])],
            ];

            $rows = [
                ['ID', $sapp->id],
                ['Environment', $sapp->environment],
                ['Internal URL', $sapp->internalUrl],
                ['Primary Domain', $sapp->primaryDomain],
                ['Config Path', "{$appPath}/config/{$sapp->environment}"],
            ];

            $io->table($headers, $rows);
        }

        $io->writeln("Paths:");
        $io->table(
            ['location', 'path'],
            [
                ['root', $appPath],
                ['code', "{$appPath}/app"],
                ['webroot', "{$appPath}/app/public"],
                ['configuration', "{$appPath}/config"]
            ]
        );

        $io->newLine();
        $io->note("Your app isn't live until you create and deploy your first release! Use the `app:deploy` command for that:");
        $io->newLine();
        $io->writeln("$ northstack app:deploy --help\n");

        $help = new HelpCommand();
        $deploy = $this->getApplication()->find('app:deploy');
        $help->setCommand($deploy);
        $help->run($input, $output);
    }
}
