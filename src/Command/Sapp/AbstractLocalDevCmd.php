<?php


namespace NorthStack\NorthStackClient\Command\Sapp;


use NorthStack\NorthStackClient\Command\Command;
use NorthStack\NorthStackClient\Docker;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


abstract class AbstractLocalDevCmd extends Command
{
    protected $skipLoginCheck = true;
    protected $commandDescription;

    protected $appData;

    use SappEnvironmentTrait;

    public function __construct()
    {
        parent::__construct($this->commandName());
    }

    protected abstract function commandName(): string;

    public function configure()
    {
        parent::configure();
        $this
            ->setDescription($this->commandDescription)
            ->addOption('env', 'e', InputOption::VALUE_REQUIRED, 'Environment (prod, test, or dev)', 'prod')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $env = $input->getOption('env');
        $this->appData = $this->getSappFromWorkingDir($env);
    }

    protected function getComposeClient()
    {
        $docker = new Docker\DockerClient();
        $compose = new Docker\DockerCompose(
            $docker,
            $this->appData['config']->{'app-type'},
            $this->buildEnvVars()
        );
        return $compose;
    }

    protected function buildComposeOptions()
    {
        return [
            'Env' => $this->buildEnvVars(),
        ];
    }

    protected function buildEnvVars()
    {
        $config = $this->appData['config'];
        $build = $this->appData['build'];
        $domains = $this->appData['domains'];
        $appName = $this->appData['name'];
        $stack = $config->{'app-type'};

        $vars = [
            'APP_NAME'             => $appName,
            'STACK'                => $stack,
            'EXPOSE_HTTP_PORT'     => 8080,
            'EXPOSE_MYSQL_PORT'    => 3306,
            'APP_ROOT'             => getcwd(),
            'APP_PUBLIC'           => getcwd() . '/app/public',
            'PRIMARY_DOMAIN'       => 'localhost',
            'COMPOSE_PROJECT_NAME' => $appName,
            'COMPOSE_FILE'         => '../docker-compose.yml:docker-compose.yml'
        ];

        if ($stack === 'wordpress')
        {
            $install = $build->{'wordpress-install'};
            $wp = [
                'WORDPRESS_VERSION'     => $build->{'wordpress-version'},
                'WORDPRESS_TITLE'       => $install->title,
                'WORDPRESS_URL'         => $install->url,
                'WORDPRESS_ADMIN_USER'  => $install->admin_user,
                'WORDPRESS_ADMIN_EMAIL' => $install->admin_email,
            ];
            $vars = array_merge($vars, $wp);
        }

        $formatted = [];
        foreach ($vars as $k => $v)
        {
            $formatted[] = "{$k}={$v}";
        }
        return $formatted;
    }

}
