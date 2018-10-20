<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\BaseLogFormat;

class AccessLogFormat extends BaseLogFormat
{
    private $fields = [
        'addr',
        'host',
        'method',
        'normalizedUri',
        'http_version',
        'http_status',
        'ua',
        'upstream_cache_status'
    ];

    public function renderLog($msg)
    {
        $data = json_decode($msg->message);
        $time = date(DATE_ISO8601, $data->{"@timestamp"});
        $items = [];
        foreach ($this->fields as $field)
        {
            $items[] = @$data->{$field};
        }
        $out = implode(' ', $items);
        $this->io->writeln("{$time} {$out}");
    }
}
