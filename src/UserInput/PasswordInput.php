<?php

namespace NorthStack\NorthStackClient\UserInput;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;

class PasswordInput extends BasicInput implements UserInputInterface
{
    protected $length;

    public function __construct($name, $description, $length = 16)
    {
        parent::__construct($name, $description, null);
        $this->length = $length;
    }

    public function getDefault()
    {
        return "<randomly-generated>";
    }

    public function getPrompt()
    {
        return $this->getDescription() . " (randomly-generated if empty): ";
    }

    public function asQuestion()
    {
        return parent::asQuestion()->setHidden(true);
    }
}
