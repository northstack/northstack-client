<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\LogFormatInterface;

class BaseLogFormat implements LogFormatInterface
{
    protected $io;
    protected $timeFmt = DATE_ISO8601;

    public function __construct($io)
    {
        $this->io = $io;
    }

    public function render($message)
    {
        if (!empty($message->type) && !empty($message->message)) {
            $this->renderInfo($message);
        } else {
            $this->renderLog($message);
        }
    }

    protected function renderLog($msg)
    {
        $this->io->writeln(json_encode($msg));
    }

    protected function renderInfo($msg)
    {
        $this->io->writeln("[$msg->type] $msg->message");
    }

    protected function renderTimestamp($ts)
    {
        return date($this->timeFmt, $ts);
    }
}
