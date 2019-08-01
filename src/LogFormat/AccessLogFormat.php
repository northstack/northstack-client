<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\TemplateLogFormat;

class AccessLogFormat extends TemplateLogFormat
{
    protected $template = '[{{timestamp}}] {{server_name}} {{addr}} {{host}} "{{method}} {{normalizedUri}} {{http_version}}" {{http_status}} "{{ua}}" {{request_time}}';

}
