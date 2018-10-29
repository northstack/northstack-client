<?php

namespace NorthStack\NorthStackClient\AppTypes;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Question\QuestionInterface;

class BaseType
{
    protected $input;
    protected $output;
    protected $questionHelper;

    protected $directories = [
        'config',
        'app',
        'app/public'
    ];

    protected $perEnvDirectories = [
        'config/{{env}}'
    ];

    protected $sapps = [];

    protected $config = [];

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        HelperInterface $qh,
        array $userConfig
    )
    {
        $this->input = $input;
        $this->output = $output;
        $this->questionHelper = $qh;

        foreach ($userConfig as $k => $v)
        {
            $this->config[$k] = $v;
        }
    }

    public function promptForArgs()
    {
    }

    public function writeConfigs($sappsCreated)
    {
        $this->sapps = $sappsCreated;
        $this->createSkeleton();
        $this->writeEnvironmentFile();
        $this->writeDomainConfigs();
        $this->writePerEnvConfigs();
        $this->writePerEnvBuildConfigs();
    }
    
    protected function writePerEnvBuildConfigs()
    {
    }

    protected function writeEnvironmentFile()
    {
        $env = [];
        foreach ($this->sapps as $sapp)
        {
            $env[$sapp->environment] = $sapp->id;
        }
        $this->writeConfigFile('config/environment.json', $env);
    }

    protected function createSkeleton()
    {
        $this->mkdirRecursive($this->directories);
        $paths = [];
        foreach ($this->perEnvDirectories as $dir)
        {
            foreach ($this->sapps as $sapp)
            {
                $paths[] = str_replace('{{env}}', $sapp->environment, $dir);
            }
        }
        $this->mkdirRecursive($paths);
    }

    protected function mkdirRecursive(array $paths)
    {
        foreach($paths as $path)
        {
            $mkPath = $this->config['baseDir'] . '/' . $path;
            if (!file_exists($mkPath))
            {
                $this->output->writeln("Creating directory {$mkPath}");
                mkdir($mkPath, 0775, true);
            }
        }
    }

    protected function writeConfigFile(string $path, array $data)
    {
        $path = $this->config['baseDir'] . '/' . $path;
        file_put_contents($path, json_encode($data), JSON_PRETTY_PRINT);
    }

    protected function writeDomainConfigs()
    {
        foreach ($this->sapps as $sapp)
        {
            $this->writeConfigFile(
                "config/{$sapp->environment}/domains.json",
                [
                    'domains' => [$this->domainForSapp($sapp)]
                ]
            );
        }
    }

    protected function domainForSapp($sapp)
    {
        if ($sapp->environment === 'prod')
        {
            return $this->config['primaryDomain'];
        }

        return "ns-{$sapp->id}.{$sapp->cluster}-northstack.com";
    }

    protected function writePerEnvConfigs()
    {
        $defaults = [
            'prod' => [
                'environment' => 'production'
            ],
            'test' => [
                'environment' => 'testing',
                'auth-type' => 'standard'
            ],
            'dev' => [
                'environment' => 'development',
                'auth-type' => 'standard'
            ]
        ];

        foreach ($this->sapps as $sapp)
        {
            $this->writeConfigFile(
                "config/{$sapp->environment}/config.json",
                $defaults[$sapp->environment]
            );
        }
    }

    protected function askQuestion(QuestionInterface $question)
    {
        return $this->questionHelper(
            $this->input,
            $this->output,
            $question
        );
    }
}
