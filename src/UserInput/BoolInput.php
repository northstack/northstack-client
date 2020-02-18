<?php


namespace NorthStack\NorthStackClient\UserInput;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class BoolInput extends AbstractUserInput implements UserInputInterface
{
    private $prompt;
    public function asInputOption()
    {
        return new InputOption(
            $this->name,
            null,
            InputOption::VALUE_NONE,
            $this->getDescription()
        );
    }

    public function getPrompt()
    {
    }

    public function asQuestion()
    {
        return new ConfirmationQuestion(
            $this->getPrompt(),
            $this->getDefault()
        );
    }
}
