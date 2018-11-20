<?php
namespace NorthStack\NorthStackClient\Command;

use NorthStack\NorthStackClient\Command\LoginRequiredTrait;

class Command extends \duncan3dc\Console\Command
{
    use LoginRequiredTrait;
    protected $lock = false;
}
