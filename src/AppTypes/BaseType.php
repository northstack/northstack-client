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

    /**
     * Supported environment files. All of these with the exception of `domains` can override
     * the related "shared" App-level field.
     *
     * @var array An array of the Sapp field matched to its config filename
     */
    protected static $envFiles = [
        'configBuild' => 'build.json',
        'config' => 'config.json',
        'domains' => 'domains.json',
        'configGatewayCust' => 'gateway.json',
    ];

    protected $app;
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
        $this->config = $userConfig;
    }

    public function writeConfigs($app)
    {
        $this->app = $app;
        $this->sapps = $app->sapps;
        $this->createSkeleton();
        $this->writeEnvironmentFile();
        $this->writePerEnvConfigs();
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

    protected function writeEnvironmentFile()
    {
        $env = [];
        foreach ($this->sapps as $sapp)
        {
            $env[$sapp->environment] = $sapp->id;
        }
        $this->writeConfigFile('config/environment.json', $env);
    }

    protected function writeSharedConfigFiles()
    {
        foreach ([
            'shared-build.json' => $this->app->sharedConfigBuild,
            'shared-config.json' => $this->app->sharedConfig,
            'shared-gateway.json' => $this->app->sharedConfigGatewaySys,
                 ] as $filename => $data) {
            if (!$data) {
                continue;
            }

            $this->writeConfigFile('config/' . $filename, $data);
        }
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

    /**
     * @param string $path
     * @param object|array $data
     */
    protected function writeConfigFile(string $path, $data)
    {
        $path = $this->config['baseDir'] . '/' . $path;
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function writePerEnvConfigs()
    {
        foreach ($this->sapps as $sapp)
        {
            foreach (self::$envFiles as $sappKey => $filename) {
                $this->writeConfigFile(
                    "config/{$sapp->environment}/{$filename}",
                    $sapp->{$sappKey}
                );
            }
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
