<?php
namespace NorthStack\NorthStackClient\Command;

class Command extends \duncan3dc\Console\Command
{
    use LoginRequiredTrait;
    protected $lock = false;
}
