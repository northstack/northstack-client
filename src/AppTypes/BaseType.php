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
    public $config = [];
    protected $input;
    protected $output;
    /**
     * @var HelperInterface|QuestionHelper
     */
    protected $questionHelper;
    protected $app;
    protected $sapps = [];
    protected static $args = [];

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

    public static function getArgs()
    {
        return static::$args;
    }

    public function promptForArgs()
    {
        $options = $this->input->getOptions();

        foreach (static::$args as $name => $arg) {
            // skip the question if the user already set the value in an option
            if (null !== $options[$name]) {
                $this->config[$name] = $options[$name];
                continue;
            }

            if (isset($arg['depends'])) {
                $depends = $arg['depends'];
                if (!$this->config[$depends]) {
                    continue;
                }
            }

            $prompt = $arg['prompt'];
            $default = $this->defaultValue($arg['default']);
            if (isset($default)) {
                $_default = ($default === false) ? 'false' : (string)$default;
                $prompt .= " (Default: {$_default})";
            }

            $prompt .= " ";

            $question = null;
            if (isset($arg['type']) && $arg['type'] === 'bool') {
                $question = new ConfirmationQuestion(
                    $prompt,
                    $default
                );
            } elseif (isset($arg['choices'])) {
                $question = new ChoiceQuestion(
                    $prompt,
                    $arg['choices'],
                    $default
                );
            } else {
                $question = new Question($prompt, $default);
            }

            if (array_key_exists('passwordInput', $arg) && ($arg['passwordInput'] === true)) {
                $question->setHidden(true);
            }

            $answer = $this->askQuestion($question);

            if (
                array_key_exists('isRandom', $arg) &&
                ($arg['isRandom'] === true) &&
                ($answer === $arg['default'])
            ) {
                $answer = bin2hex(random_bytes($arg['randomLen']));
            }

            $this->config[$name] = $answer;
        }
    }

    protected function defaultValue($param)
    {
        if (strpos($param, '$') === 0) {
            return $this->config[substr($param, 1)];
        }

        return $param;
    }

    protected function askQuestion($question)
    {
        return $this->questionHelper->ask(
            $this->input,
            $this->output,
            $question
        );
    }

    public function getFrameworkConfig()
    {
        return null;
    }

}
