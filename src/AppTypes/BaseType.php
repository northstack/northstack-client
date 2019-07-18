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

    abstract public static function getArgs();

    public static function getInputOptions()
    {
        foreach (static::getArgs() as $arg)
        {
            if ($arg->getName() === 'frameworkVersion')
            {
                continue;
            }
            yield $arg->asInputOption();
        }
    }

    public function promptForArgs()
    {
        $options = $this->input->getOptions();
        foreach (static::getArgs() as $arg) {
            $name = $arg->getName();

            $input = $this->input->getOption($name);
            // skip the question if the user already set the value in an option
            if (null !== $input && $input !== $arg->getDefault())
            {
                $this->config[$name] = $input;
                continue;
            }

            /*
            if (isset($arg['depends'])) {
                $depends = $arg['depends'];
                if (!$this->config[$depends]) {
                    continue;
                }
            }
            */

            $arg->setDefault(
                $this->defaultValue(
                    $arg->getDefault()
                )
            );

            $answer = $this->askQuestion($arg->asQuestion());

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
