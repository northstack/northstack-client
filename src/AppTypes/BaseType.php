<?php

namespace NorthStack\NorthStackClient\AppTypes;

use Symfony\Component\Console\Helper\QuestionHelper;
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
    /**
     * @var HelperInterface|QuestionHelper
     */
    protected $questionHelper;

    protected static $directories = [
        'config',
        'scripts',
        'app',
        'app/public'
    ];

    protected static $perEnvDirectories = [
        'config/{{env}}'
    ];

    protected $sapps = [];

    public $config = [];

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

    abstract protected function writePerEnvBuildConfigs();

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
        $this->mkdirRecursive(self::$directories);
        $paths = [];
        foreach (self::$perEnvDirectories as $dir)
        {
            foreach ($this->sapps as $sapp)
            {
                $paths[] = str_replace('{{env}}', $sapp->environment, $dir);
            }
            $paths[] = str_replace('{{env}}', 'local', $dir);
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
                if (!mkdir($mkPath, 0775, true) && !is_dir($mkPath)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $mkPath));
                }
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
        foreach ($this->sapps as $sapp) {
            $this->writeConfigFile(
                "config/{$sapp->environment}/domains.json",
                $sapp->domains
            );
        }
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
            ],
        ];

        foreach ($this->sapps as $sapp)
        {
            $this->writeConfigFile(
                "config/{$sapp->environment}/config.json",
                $defaults[$sapp->environment]
            );
        }
        $this->writeConfigFile(
            "config/local/config.json",
            $defaults['dev']
        );
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
            if (isset($arg['type']) && $arg['type'] === 'bool')
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

    public function getFrameworkConfig()
    {
        return null;
    }

}
