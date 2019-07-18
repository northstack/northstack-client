<?php


namespace NorthStack\NorthStackClient\UserInput;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class BasicInput extends AbstractUserInput implements UserInputInterface
{
    public function asInputOption()
    {
        return new InputOption(
            $this->name,
            null,
            InputOption::VALUE_REQUIRED,
            $this->getDescription(),
            $this->getDefault(),
        );
    }

    public function asQuestion()
    {
        return new Question(
            $this->getPrompt(),
            $this->getDefault()
        );
    }
}
