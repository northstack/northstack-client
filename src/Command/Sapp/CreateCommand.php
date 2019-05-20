<?php


namespace NorthStack\NorthStackClient\Command\Sapp;

use GuzzleHttp\Exception\ClientException;
use NorthStack\NorthStackClient\API\Sapp\SappClient;
use NorthStack\NorthStackClient\API\Orgs\OrgsClient;
use NorthStack\NorthStackClient\API\Sapp\SecretsClient;
use NorthStack\NorthStackClient\AppTypes\BaseType;
use NorthStack\NorthStackClient\AppTypes\JekyllType;
use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\OauthCommandTrait;
use NorthStack\NorthStackClient\OrgAccountHelper;

use NorthStack\NorthStackClient\AppTypes\StaticType;
use NorthStack\NorthStackClient\AppTypes\WordPressType;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    use OauthCommandTrait;
    use CommandFetchAppTrait;
    /**
     * @var SappClient
     */
    protected $sappClient;

    /**
     * @var OrgAccountHelper
     */
    private $orgAccountHelper;
    /**
     * @var SecretsClient
     */
    private $secretsClient;

    public function __construct(
        SappClient $sappClient,
        OrgsClient $orgs,
        OrgAccountHelper $orgAccountHelper,
        SecretsClient $secretsClient
    )
    {
        parent::__construct('app:create');
        $this->sappClient = $sappClient;
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
            ->addOption('frameworkVersion', null, InputOption::VALUE_REQUIRED, 'Framework version (if not static app)')
            ->addOption('frameworkConfig', null, InputOption::VALUE_REQUIRED, 'Framework config object')
            ->addOption('cluster', null, InputOption::VALUE_REQUIRED, 'Deployment location', 'dev-us-east-1')
            ->addOption('orgId', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
            ->addOption('useDefaultLocation', null, InputOption::VALUE_REQUIRED, 'Only needed if you have access to multiple organizations')
            ->addOption('appSlug', null, InputOption::VALUE_REQUIRED, 'Name to use for the app\'s local directory and local reference')
        ;

        foreach (array_merge(BaseType::getArgs(), StaticType::getArgs(), JekyllType::getArgs(), WordPressType::getArgs()) as $optKey => $optArgs) {
            if ('frameworkVersion' === $optKey) {
                continue;
            }

            $this->addOption(
                $optKey,
                null,
                InputOption::VALUE_OPTIONAL,
                $optArgs['prompt'],
                null
            );
        }

        $this->addOauthOptions();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->isDebug()) {
            $this->sappClient->setDebug();
        }

        $args = $input->getArguments();
        $options = $input->getOptions();

        $questionHelper = $this->getHelper('question');
        $appSlug = $input->getOption('appSlug');
        if (!$appSlug) {
            $appSlug = $this->getLocalAppSlug($input->getArgument('name'));
            $output->writeln('No app slug set. The local app\'s slug will be: ' . $appSlug);
        } else {
            $this->getLocalAppSlug($appSlug);
        }

        $appPath = $this->getLocalAppDir($input, $output, $questionHelper, $appSlug);
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
            $r = $this->sappClient->createApp(
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
        $app = json_decode($r->getBody()->getContents());

        // Go ahead and try to set the secret for initial WP admin pass
        if (!empty($appTemplate->config['wpAdminPass'])) {
            $output->writeln('Setting initial WP admin password secrets...');
            /**
             * @TODO: we should throw a warning if the chosen admin pass has a single quote
             * @ref: https://github.com/wp-cli/wp-cli/issues/5089
             */
            try {
                // @TODO: build a `setMultiple` secrets endpoint to avoid multiple calls here
                foreach ($app->sapps as $sapp) {
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

        $this->setupLocalApp($input, $output, $app, $appSlug, $appPath);
        $output->writeln("Woohoo! Your NorthStack app ({$app->appName}) was created successfully.");
        $this->printSuccess($input, $output, $app, $appSlug, $appPath, true);
    }
}
