<?php


namespace NorthStack\NorthStackClient\Command\LocalDev;


use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Command\Sapp\SappEnvironmentTrait;
use NorthStack\NorthStackClient\Command\UserSettingsCommandTrait;
use NorthStack\NorthStackClient\Docker;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


abstract class AbstractLocalDevCmd extends Command
{
    use UserSettingsCommandTrait;
    use SappEnvironmentTrait;

    protected static $defaultEnv = 'local';

    protected $commandDescription;

    protected $appData;

    /**
     * @var Docker\Action\BaseAction
     */
    protected $action;
    /**
     * @var string
     */
    protected $localAppRoot;

    public function __construct()
    {
        $this->skipLoginCheck = true;
        parent::__construct($this->commandName());
    }

    abstract protected function commandName(): string;

    abstract protected function getDockerAction();

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription($this->commandDescription)
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Environment (prod, test, dev, or local)', self::$defaultEnv)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getOption('env');
        $this->appData = $this->getSapp($input->getArgument('name'), $env);

        $this->localAppRoot = $this->findDefaultAppsDir($input, $output, $this->getHelper('question'))
            .'/'.$input->getArgument('name');

        $docker = new Docker\DockerClient();
        $action = $this->getDockerAction();
        $this->action = new $action(
            $this->appData['config']->app_type,
            $docker,
            $input,
            $output,
            $this->buildEnvVars(),
            $this->appData,
            $this->localAppRoot
        );
    }

    protected function getAction()
    {
        return $this->action;
    }

    protected function buildEnvVars()
    {
        $config = $this->appData['config'];
        $build = $this->appData['build'];
        $appName = $this->appData['name'];
        $appId = $this->appData['id'];
        $stack = strtoupper($config->app_type);

        $uid = getenv('NORTHSTACK_UID');
        $user = 'ns';
        $gid = getenv('NORTHSTACK_GID');
        $group = 'ns';

        switch ($stack) {
            case 'JEKYLL':
            case 'GATSBY':
                $public = '/app/_site';
                break;
            case 'WORDPRESS':
            case 'STATIC':
            default:
                $public = '/app/public';
                break;
        }
        // TODO: let users configure some of this stuff via $APP_ROOT/config/localdev.json
        $vars = [
            'APP_NAME' => $appName,
            'APP_ID' => $appId,
            'STACK' => $stack,
            'EXPOSE_HTTP_PORT' => 8080,
            'EXPOSE_MYSQL_PORT' => 3306,
            'APP_ROOT' => (string)$this->localAppRoot,
            'APP_APP' => "$this->localAppRoot/app",
            'APP_PUBLIC' => "$this->localAppRoot/${public}",
            'PRIMARY_DOMAIN' => 'localhost',
            'COMPOSE_PROJECT_NAME' => $appName,

            'NORTHSTACK_USER' => $user,
            'NORTHSTACK_UID' => getenv('NORTHSTACK_UID') ?: $uid,
            'NORTHSTACK_GROUP' => $group,
            'NORTHSTACK_GID' => getenv('NORTHSTACK_GID') ?: $gid,
            'FRAMEWORK_VERSION' => $build->framework_version,
        ];

        // TODO: move this logic somewhere else
        if ($stack === 'WORDPRESS') {
            $install = $build->framework_config;
            $wp = [

                'WORDPRESS_TITLE' => $install->title,
                'WORDPRESS_URL' => $install->url,
                'WORDPRESS_ADMIN_USER' => $install->admin_user,
                'WORDPRESS_ADMIN_EMAIL' => $install->admin_email,
//                'WORDPRESS_ADMIN_PASS' => $install->admin_pass,
                'WORDPRESS_ADMIN_PASS' => 'password', /** TODO: ask user for a local pass to use, or pull/set one in secrets just for local? */
            ];
            $vars = array_merge($vars, $wp);
        }

        $formatted = [];
        foreach ($vars as $k => $v) {
            $formatted[] = "{$k}={$v}";
        }

        return $formatted;
    }

}
