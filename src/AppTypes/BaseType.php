<?php

namespace NorthStack\NorthStackClient\AppTypes;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class BaseType
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

    protected $args = [];

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
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
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
                'environment' => 'test',
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

    protected function askQuestion($question)
    {
        return $this->questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );
    }

    public function promptForArgs()
    {
        foreach ($this->args as $name => $arg)
        {

            if (isset($arg['depends']))
            {
                $depends = $arg['depends'];
                if (!$this->config[$depends])
                {
                    continue;
                }
            }

            $prompt = $arg['prompt'];
            $default = $this->defaultValue($arg['default']);
            if (isset($default))
            {
                $_default = ($default === false) ? 'false' : (string) $default;
                $prompt .= " (Default: {$_default})";
            }

            $prompt .= " ";

            $question = null; 
            if ($arg['type'] === 'bool')
            {
                $question = new ConfirmationQuestion(
                    $prompt,
                    $default
                );
            } elseif (isset($arg['choices']))
            {
                $question = new ChoiceQuestion(
                    $prompt,
                    $arg['choices'],
                    $default
                );
            } else
            {
                $question = new Question($prompt, $default);
            }

            if (array_key_exists('passwordInput', $arg) && ($arg['passwordInput'] === true))
            {
                $question->setHidden(true);
            }

            $answer = $this->askQuestion($question);

            if (
                array_key_exists('isRandom', $arg) &&
                ($arg['isRandom'] === true) &&
                ($answer === $arg['default'])
                )
            {
                $answer = bin2hex(random_bytes($arg['randomLen']));
            }

            $this->config[$name] = $answer;
        }
    }

    protected function defaultValue($param)
    {
        if (strpos($param, '$') === 0)
        {
            return $this->config[substr($param, 1)];
        }

        return $param;
    }

}
