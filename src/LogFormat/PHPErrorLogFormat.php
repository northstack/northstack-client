<?php

namespace NorthStack\NorthStackClient\LogFormat;

use NorthStack\NorthStackClient\LogFormat\BaseLogFormat;
use NorthStack\NorthStackClient\LogFormat\TemplateLogFormat;

class PHPErrorLogFormat extends TemplateLogFormat
{
    protected $template = '[{{@timestamp}}] {{message}}';
}
