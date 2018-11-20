<?php
namespace NorthStack\NorthStackClient\Command\Auth;

use NorthStack\NorthStackClient\Command\Command;

abstract class AbstractAuthCmd extends Command
{
    protected $skipLoginCheck = true;
}
