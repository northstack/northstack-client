<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\BaseLogFormat;

class PHPErrorLogFormat extends BaseLogFormat
{
    public function renderLog($msg)
    {
        $data = json_decode($msg->message);
        $time = date(DATE_ISO8601, $data->{"@timestamp"});
        $this->io->writeln("{$time}  {$data->message}");
    }
}
