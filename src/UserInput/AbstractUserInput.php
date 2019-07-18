<?php


namespace NorthStack\NorthStackClient\UserInput;

abstract class AbstractUserInput
{
    protected $name;
    private $value;
    protected $description;
    protected $validators = [];
    protected $default = null;

    abstract public function asInputOption();
    abstract public function asQuestion();

    public function __construct($name, $description, $default = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->default = $default;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getValue($value)
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $this->validate($value);
    }

    private function validate($value)
    {
        foreach ($this->validators as $validator)
        {
            $value = $validator($value);
        }
        return $value;
    }

    public function setDefault($value)
    {
        $this->default = $value;
        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getPrompt()
    {
        $prompt = $this->getDescription();
        $default = (string) $this->getDefault();
        if ($default !== null)
        {
            $prompt .= " (default: {$default})";
        }
        $prompt .= ": ";
        return $prompt;
    }
}
