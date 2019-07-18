<?php


namespace NorthStack\NorthStackClient\UserInput;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ChoiceInput extends BasicInput implements UserInputInterface
{
    private $choices = [];

    public function setChoices($choices)
    {
        $this->choices = $choices;
        return $this;
    }

    public function asQuestion()
    {
        return new ChoiceQuestion(
            $this->name,
            $this->choices,
            $this->default
        );
    }

    public function getDescription()
    {
        $desc = $this->description;
        $desc .= " (One of: [";
        $desc .= implode(", ", $this->choices);
        $desc .= "])";
        return $desc;
    }
}
